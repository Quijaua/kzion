# kzion
Acervo Familiar de fotos

Aplicação Laravel para arquivar fotos de família, com autenticação, permissões
por pessoa, tags (pessoas), álbuns, extração de EXIF e pesquisa.

- **Backend:** Laravel 12 (PHP 8.3)
- **Frontend:** Blade + Tabler 1.7 + Tabler Icons 3.52 (pacotes npm, empacotados pelo Vite —
  não há CDN; qualquer mudança no front-end exige `npm run build`)
- **Banco:** MariaDB (produção) / SQLite (desenvolvimento e testes)
- **Imagens:** Intervention Image v4
- **EXIF:** extensão `exif` do PHP (sem ExifTool)

Documentação de arquitetura, comandos e regras de acesso está em
[`AGENTS.md`](AGENTS.md).

---

## Requisitos do servidor

- PHP >= 8.3 com extensões: `exif`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `curl`
- Composer 2
- Node.js + npm (apenas no build dos assets)
- Servidor web: nginx + PHP-FPM (recomendado) — `php artisan serve` é só para dev
- MariaDB 10.11+ (ou MySQL 8)

---

## Instalação em produção

### 1. Clonar e instalar dependências

```bash
git clone <repo> kzion && cd kzion
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 2. Configurar o ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Edite `.env` para produção:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://acervo.exemplo.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kzion
DB_USERNAME=kzion
DB_PASSWORD=senha-forte

# O disco padrão já aponta para storage/app/private (arquivos originais + thumbnails)
FILESYSTEM_DISK=local
```

### 3. Criar o banco (MariaDB)

O servidor usa autenticação `unix_socket` para o root, então crie o banco e o
usuário da aplicação com privilégios de root:

```bash
sudo mysql -u root
```

```sql
CREATE DATABASE kzion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kzion'@'127.0.0.1' IDENTIFIED BY 'senha-forte';
GRANT ALL PRIVILEGES ON kzion.* TO 'kzion'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### 4. Migrar e (opcionalmente) popular

```bash
php artisan migrate --force
```

> O seeder (`php artisan db:seed`) cria o usuário **admin@example.com / password**
> **e fotos de exemplo**. Em produção, rode o seed **apenas se** quiser os dados
> demonstrativos. Para criar só o administrador sem fotos de exemplo:
>
> ```bash
> php artisan tinker --execute="App\Models\User::firstOrCreate(['email'=>'admin@example.com'],['name'=>'Administrador','password'=>bcrypt('password'),'role'=>'admin','active'=>true]);"
> ```
> Troque a senha após o primeiro login.

### 5. Permissões de escrita

O app grava em `storage/app/private` e em `storage/framework`/`bootstrap/cache`:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Otimizar (opcional, recomendado)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Agendador (cron)

O Laravel precisa de um cron chamando o agendador a cada minuto. Hoje ele
apaga, uma vez por dia, os dispositivos confiáveis vencidos do desafio JS
(`model:prune` em `routes/console.php`).

Rode o cron com o **mesmo usuário do PHP-FPM** (normalmente `www-data`). Se
rodar como `root`, ele cria arquivos em `storage/` (como o
`storage/logs/laravel.log`) que o PHP-FPM depois não consegue escrever.

Confira o usuário do PHP-FPM e o dono do `storage`:

```bash
grep -E '^(user|group)\s*=' /etc/php/*/fpm/pool.d/*.conf
ls -ld storage storage/logs
```

Crie o cron para esse usuário, com `sudo crontab -u www-data -e`:

```
* * * * * cd /var/www/kzion && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Ou com um arquivo `/etc/cron.d/kzion`, em que o usuário entra como campo:

```
* * * * * www-data cd /var/www/kzion && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

- O `www-data` costuma ter shell `nologin`, mas isso não atrapalha: o cron
  executa os comandos com `/bin/sh`.
- Use o caminho completo do PHP (`which php`), porque o cron tem um `PATH`
  mínimo. Com mais de uma versão instalada, use a mesma do FPM (por exemplo,
  `/usr/bin/php8.3`).

Teste como o próprio usuário. Os dois comandos devem rodar sem erro de
permissão, e o `schedule:list` deve mostrar o `model:prune` diário:

```bash
sudo -u www-data /usr/bin/php artisan schedule:list
sudo -u www-data /usr/bin/php artisan schedule:run
```

---

## Servidor web (nginx + PHP-FPM)

Exemplo de virtual host. Ajuste `root` e o socket do PHP-FPM (`php8.3-fpm.sock`)
conforme sua distribuição.

```nginx
server {
    listen 80;
    server_name acervo.exemplo.com;
    root /var/www/kzion/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Recarregue o nginx e o PHP-FPM após salvar.

> As imagens originais e os thumbnails ficam em `storage/app/private` e são
> servidos pela aplicação (rotas `photos.file` / `photos.thumbnail`), que
> reaplicam a verificação de permissão. **Não é necessário** `php artisan storage:link`.

---

## Atualizando a aplicação

```bash
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Backup

O acervo é o par **banco de dados + arquivos em `storage/app/private`**:

```bash
# Banco
mysqldump -u kzion -p kzion > backup_kzion_$(date +%F).sql

# Arquivos (originais + thumbnails)
tar czf backup_storage_$(date +%F).tar.gz storage/app/private
```

Restaurar: recrie o banco (`mysql kzion < backup.sql`) e extraia o tar em
`storage/app/private`. Como o banco guarda apenas o `storage_path` relativo,
a restauração é direta.

---

## Ambiente de desenvolvimento local

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed      # admin@example.com / password + dados de exemplo
php artisan serve
```

Testes: `php artisan test` (usa SQLite em memória).
