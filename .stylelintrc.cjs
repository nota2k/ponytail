module.exports = {
  extends: [
    'stylelint-config-standard-scss',
  ],
  plugins: [
    '@stylistic/stylelint-plugin',
  ],
  rules: {
    // Ajoutez vos règles ici si besoin
  },
  ignoreFiles: [
    'dist/**/*',
    'assets/dist/**/*',
    'node_modules/**/*',
  ],
};


