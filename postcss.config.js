// O Tailwind foi removido do projeto: o Tabler (Bootstrap 5) é o único design
// system, e o preflight do Tailwind carregava depois dele e zerava botões,
// títulos, bordas e inputs. Aqui sobra só o autoprefixer.
export default {
    plugins: {
        autoprefixer: {},
    },
};
