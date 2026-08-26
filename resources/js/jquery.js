// jQuery has to be on `window` BEFORE any plugin (Select2, Tabler) is imported,
// because their UMD wrappers look for `window.jQuery` at module-evaluation time.
// Keeping this in its own module guarantees the assignment runs first: ES module
// imports are hoisted and evaluated in dependency order, so `import './jquery.js'`
// at the top of app.js is evaluated before the plugin imports below it.
import $ from 'jquery';

window.$ = window.jQuery = $;

export default $;
