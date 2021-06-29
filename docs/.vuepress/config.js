module.exports = {
  description: 'Thunder is a Drupal distribution for professional publishers.',
  head: [['link', { rel: 'icon', href: '/thunder.svg' }]],
  themeConfig: {
    logo: '/thunder.svg',
    navbar: [
      {
        text: 'User Guide',
        link: '/user-guide/feature-overview.html',
      },
      {
        text: 'Developer Guide',
        link: '/developer-guide/setup/install.md',
      },
      {
        text: 'Contribute',
        link: '../../contributing.md',
      },
    ],
    sidebar: {
      '/user-guide/': [
        '/user-guide/feature-overview.md'
      ],
      '/developer-guide/': [
        {
          text: 'Setup Thunder',
          children: [
            '/developer-guide/setup/install.md',
            '/developer-guide/setup/update.md',
            '/developer-guide/setup/extend.md',
          ],
        },
        {
          text: 'Operating',
          children: [
            '/developer-guide/setup/varnish.md',
          ],
        },
        {
          text: 'Use Thunder',
          children: [
            '/developer-guide/use/headless.md',
          ],
        },
        {
          text: 'Migration',
          children: [
            '/developer-guide/migration/migrate-3-6.md',
            '/developer-guide/migration/migrate-2-3.md',
          ],
        }
      ],
    }
  },
}
