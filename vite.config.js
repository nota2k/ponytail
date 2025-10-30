import uupVite from "vite-plugin-uup";
import autoprefixer from "autoprefixer";
import liveReload from "vite-plugin-live-reload";
import { checker } from "vite-plugin-checker";
import externalGlobals from "rollup-plugin-external-globals";
import { defineConfig } from "vite";

const port = process.env.VITE_PORT || 1559;

export default defineConfig({
  input: [
    "resources/js/main.js",
    "resources/js/editor.js",
    "resources/scss/theme.scss",
    "resources/scss/block-editor.scss",
  ],
  port,

  plugins: [
    uupVite(),

    liveReload(__dirname + "/**/**.php"),

    checker({
      stylelint: {
        lintCommand: "stylelint ./resources/scss/**/*.scss --fix",
      },
    }),

    externalGlobals({
      jquery: "jQuery",
    }),
  ],
  css: {
    postcss: {
      plugins: [autoprefixer()],
    },
    devSourcemap: true,
  },
  resolve: {
    alias: {
      // "@mixins": "/resources/scss/generic/mixins",
      // "@functions": "/resources/scss/generic/functions",
      // "@variables": "/resources/scss/generic/variables",
      // "@components": "/resources/scss/components",
      // "@generic": "/resources/scss/generic",
    },
  },
});
