import js from '@eslint/js';
import prettier from 'eslint-config-prettier/flat';
import importPlugin from 'eslint-plugin-import';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import unusedImports from 'eslint-plugin-unused-imports';
import globals from 'globals';
import { configs as typescriptConfigs } from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
    js.configs.recommended,
    reactHooks.configs.flat['recommended-latest'],
    ...typescriptConfigs.recommended,
    {
        rules: {
            '@typescript-eslint/consistent-type-imports': [
                'error',
                {
                    prefer: 'type-imports',
                    fixStyle: 'inline-type-imports',
                },
            ],
        },
    },
    {
        ...react.configs.flat.recommended,
        languageOptions: {
            ...react.configs.flat.recommended.languageOptions,
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            ...react.configs.flat.recommended.rules,
            ...react.configs.flat['jsx-runtime'].rules, // Required for React 17+
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
    {
        ...importPlugin.flatConfigs.recommended,
        settings: {
            ...importPlugin.flatConfigs.recommended.settings,
            ...importPlugin.flatConfigs.react.settings,
            'import/extensions': importPlugin.flatConfigs.typescript.settings['import/extensions'],
            'import/external-module-folders':
                importPlugin.flatConfigs.typescript.settings['import/external-module-folders'],
            'import/resolver': {
                typescript: {
                    project: './tsconfig.json',
                },
            },
        },
        rules: {
            ...importPlugin.flatConfigs.recommended.rules,
            ...importPlugin.flatConfigs.typescript.rules,
            ...importPlugin.flatConfigs.react.rules,
            // Wayfinder controllers are imported as defaults by convention (see AGENTS.md),
            // and every action they expose also exists as a named export.
            'import/no-named-as-default-member': 'error',
            'import/order': [
                'warn',
                {
                    groups: ['builtin', 'external', 'internal'],
                    'newlines-between': 'always',
                    alphabetize: {
                        order: 'asc',
                        caseInsensitive: true,
                    },
                },
            ],
        },
    },
    {
        files: ['landing/**/*.mjs'],
        languageOptions: {
            globals: {
                ...globals.node,
            },
        },
    },
    {
        plugins: {
            'unused-imports': unusedImports,
        },
        rules: {
            'unused-imports/no-unused-imports': 'error',
        },
    },
    {
        files: [
            'resources/js/pages/storefront/**',
            'resources/js/components/storefront/**',
            'resources/js/layouts/storefront/**',
            'resources/js/hooks/storefront/**',
            'resources/js/storefront.tsx',
            'resources/js/create-app.tsx',
        ],
        rules: {
            'no-restricted-imports': [
                'error',
                {
                    patterns: [
                        {
                            group: ['@/components/ui/*', 'radix-ui', '@radix-ui/*'],
                            message:
                                'The storefront ships without shadcn/Radix. Build a storefront component or use a framework-free hook instead.',
                        },
                    ],
                },
            ],
        },
    },
    {
        ignores: [
            '.claude/worktrees/**',
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'build',
            'tailwind.config.js',
            'vite.config.ts',
            'resources/js/actions/**',
            'resources/js/routes/**',
            'resources/js/components/ui/*',
            'resources/js/types/translations.ts',
        ],
    },
    prettier, // Turn off all rules that might conflict with Prettier
];
