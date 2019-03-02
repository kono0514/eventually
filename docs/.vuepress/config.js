module.exports = {
    title: 'Eventually',
    description: 'The missing many-to-many Eloquent events.',
    base: '/eventually/',
    dest: 'public',

    head: [
        ['meta', { name: 'keywords', content: 'attach, detach, eloquent, events, eventually, laravel, lumen, many-to-many, pivot, relations, sync, toggle, update' }]
    ],

    plugins: [
        '@vuepress/back-to-top',
        '@vuepress/active-header-links'
    ],

    themeConfig: {
        repo: 'https://gitlab.com/altek/eventually',
        repoLabel: 'Contribute!',
        editLinks: true,
        editLinkText: 'Improve this page!',
        docsDir: 'docs',

        nav: [
            { text: 'Home', link: '/' },
            { text: 'Packagist', link: 'https://packagist.org/packages/altek/eventually' },
            { text: 'Laravel', link: 'https://laravel.com' }
        ],

        sidebar: [
            '/',

            {
                title: 'Getting Started',
                collapsable: false,
                children: [
                    ['installation', 'Installation'],
                ]
            },

            {
                title: 'Events',
                collapsable: false,
                children: [
                    ['events', 'Events']
                ]
            }
        ]
    }
};
