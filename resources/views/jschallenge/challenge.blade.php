<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="refresh" content="30">
<title>Verificando seu navegador…</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu,sans-serif;background:#fff;color:#1a1a1a;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px}
.c{text-align:center;max-width:560px;width:100%}
.s{width:56px;height:56px;margin:0 auto 28px;border:4px solid #e5e7eb;border-top-color:#2563eb;border-radius:50%;animation:r 1s linear infinite}
@keyframes r{to{transform:rotate(360deg)}}
h1{font-size:22px;font-weight:600;line-height:1.4;margin-bottom:12px}
p{font-size:14px;color:#666;line-height:1.5}
.err .s{display:none}
@media (prefers-color-scheme:dark){body{background:#111;color:#eee}p{color:#aaa}.s{border-color:#333;border-top-color:#60a5fa}}
</style>
</head>
<body>
<div class="c" id="box">
  <div class="s"></div>
  <h1>Verificando seu navegador antes de acessar<br><span id="host"></span></h1>
  <p id="msg">Isso leva só alguns segundos…</p>
</div>
<noscript><p style="text-align:center;margin-top:16px">Ative o JavaScript no navegador para acessar este site.</p></noscript>
<script>
(function () {
  var cfg = {
    token: @json($token),
    difficulty: @json($difficulty),
    minDelay: @json($minDelay),
    verifyUrl: @json($verifyUrl)
  };

  document.getElementById('host').textContent = location.hostname;

  // ----- Solver: SHA-256 síncrono em JS puro (funciona também em HTTP, sem crypto.subtle) -----
  function solverFactory(scope) {
    var K = [], p = 2, n = 0;
    while (n < 64) {
      for (var d = 2, prime = true; d * d <= p; d++) if (p % d === 0) { prime = false; break; }
      if (prime) K[n++] = (Math.pow(p, 1 / 3) * 4294967296) | 0;
      p++;
    }
    var W = new Int32Array(64);

    function sha256(msg) {
      var H = [0x6a09e667, 0xbb67ae85 | 0, 0x3c6ef372, 0xa54ff53a | 0, 0x510e527f, 0x9b05688c | 0, 0x1f83d9ab, 0x5be0cd19];
      var len = msg.length, words = (((len + 8) >> 6) + 1) << 4, M = new Int32Array(words), i, j, t;
      for (i = 0; i < len; i++) M[i >> 2] |= msg.charCodeAt(i) << (24 - (i & 3) * 8);
      M[len >> 2] |= 0x80 << (24 - (len & 3) * 8);
      M[words - 1] = len * 8;
      for (j = 0; j < words; j += 16) {
        var a = H[0], b = H[1], c = H[2], dd = H[3], e = H[4], f = H[5], g = H[6], h = H[7];
        for (t = 0; t < 64; t++) {
          if (t < 16) W[t] = M[j + t];
          else {
            var x = W[t - 15], y = W[t - 2];
            W[t] = (((x >>> 7 | x << 25) ^ (x >>> 18 | x << 14) ^ (x >>> 3)) + W[t - 7] +
                    ((y >>> 17 | y << 15) ^ (y >>> 19 | y << 13) ^ (y >>> 10)) + W[t - 16]) | 0;
          }
          var T1 = (h + ((e >>> 6 | e << 26) ^ (e >>> 11 | e << 21) ^ (e >>> 25 | e << 7)) + ((e & f) ^ (~e & g)) + K[t] + W[t]) | 0;
          var T2 = (((a >>> 2 | a << 30) ^ (a >>> 13 | a << 19) ^ (a >>> 22 | a << 10)) + ((a & b) ^ (a & c) ^ (b & c))) | 0;
          h = g; g = f; f = e; e = (dd + T1) | 0; dd = c; c = b; b = a; a = (T1 + T2) | 0;
        }
        H[0] = (H[0] + a) | 0; H[1] = (H[1] + b) | 0; H[2] = (H[2] + c) | 0; H[3] = (H[3] + dd) | 0;
        H[4] = (H[4] + e) | 0; H[5] = (H[5] + f) | 0; H[6] = (H[6] + g) | 0; H[7] = (H[7] + h) | 0;
      }
      return H;
    }

    function zeroBits(H) {
      for (var i = 0, z = 0; i < 8; i++) {
        if (H[i] === 0) { z += 32; continue; }
        return z + Math.clz32(H[i]);
      }
      return z;
    }

    // Procura em [start, start+count); devolve o nonce ou -1
    function solve(token, diff, start, count) {
      var prefix = token + ':';
      for (var k = start, end = start + count; k < end; k++) {
        if (zeroBits(sha256(prefix + k)) >= diff) return k;
      }
      return -1;
    }

    if (scope) {
      scope.onmessage = function (ev) {
        var s = 0, r;
        while ((r = solve(ev.data.t, ev.data.d, s, 100000)) < 0) s += 100000;
        scope.postMessage(r);
      };
    }
    return { solve: solve, sha256: sha256, zeroBits: zeroBits };
  }

  // Roda num Web Worker (não trava a página); se não der, roda em fatias na thread principal
  function findNonce(cb) {
    try {
      var src = '(' + solverFactory.toString() + ')(self);';
      var url = URL.createObjectURL(new Blob([src], { type: 'text/javascript' }));
      var w = new Worker(url);
      w.onmessage = function (ev) { w.terminate(); URL.revokeObjectURL(url); cb(ev.data); };
      w.onerror = function (ev) { ev.preventDefault && ev.preventDefault(); w.terminate(); mainThread(cb); };
      w.postMessage({ t: cfg.token, d: cfg.difficulty });
    } catch (e) {
      mainThread(cb);
    }
  }

  function mainThread(cb) {
    var s = solverFactory(null), start = 0;
    (function step() {
      var r = s.solve(cfg.token, cfg.difficulty, start, 20000);
      if (r >= 0) return cb(r);
      start += 20000;
      setTimeout(step, 0);
    })();
  }

  function fail(text) {
    document.getElementById('box').className = 'c err';
    document.getElementById('msg').textContent = text;
  }

  // Evita loop infinito quando o navegador bloqueia cookies
  // (conta só tentativas dos últimos 2 minutos)
  var tries = 0;
  try {
    var st = JSON.parse(sessionStorage.getItem('__jsc_tries') || 'null');
    tries = (st && Date.now() - st.ts < 120000) ? st.n + 1 : 1;
    sessionStorage.setItem('__jsc_tries', JSON.stringify({ n: tries, ts: Date.now() }));
  } catch (e) {}
  if (tries > 3) {
    try { sessionStorage.removeItem('__jsc_tries'); } catch (e) {}
    return fail('Não foi possível concluir a verificação. Verifique se os cookies estão permitidos para este site e recarregue a página.');
  }

  var t0 = Date.now();
  findNonce(function (nonce) {
    var wait = Math.max(0, cfg.minDelay - (Date.now() - t0));
    setTimeout(function () {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', cfg.verifyUrl);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) location.reload();
        else if (xhr.status === 429) fail('Muitas tentativas. Aguarde um minuto e recarregue a página.');
        else location.reload(); // token expirado etc.: recarrega e recebe um novo desafio
      };
      xhr.onerror = function () { fail('Falha de conexão. Recarregue a página.'); };
      xhr.send('token=' + encodeURIComponent(cfg.token) + '&nonce=' + nonce);
    }, wait);
  });
})();
</script>
</body>
</html>
