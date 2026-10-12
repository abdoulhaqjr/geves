import js from '@eslint/js';
import globals from 'globals';
import prettier from 'eslint-config-prettier';

/**
 * Lint du JavaScript front-end (resources/js). On applique le jeu « recommended »
 * (détection de vrais bugs) ; la mise en forme est déléguée à Prettier (règles de
 * style désactivées via eslint-config-prettier). Les avertissements (ex. variables
 * inutilisées) sont tolérés pour ne pas bloquer sur le code hérité - à réduire
 * progressivement, à l'image de la baseline PHPStan.
 */
export default [
    {
        ignores: ['public/build/**', 'vendor/**', 'node_modules/**'],
    },
    js.configs.recommended,
    prettier,
    {
        files: ['resources/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2023,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                // Bibliothèques chargées globalement (CDN / bundle) sans import.
                Chart: 'readonly',
                Html5Qrcode: 'readonly',
            },
        },
        rules: {
            // Bugs réels : erreurs bloquantes.
            'no-cond-assign': ['error', 'except-parens'],
            'no-constant-condition': ['error', { checkLoops: false }],
            // Style / hérité : avertissements non bloquants.
            'no-unused-vars': ['warn', { args: 'none', caughtErrors: 'none' }],
            'no-empty': ['warn', { allowEmptyCatch: true }],
        },
    },
];
