const {path} = require('@vuepress/utils')
module.exports = {
  title: 'Thunder',
  description: 'Thunder is a Drupal distribution for professional publishers.',
  head: [['link', {rel: 'icon', href: '/thunder.svg'}]],
  theme: path.resolve(__dirname, './theme'),
  themeConfig: {
    logo: '/thunder.svg',
    repo: 'https://github.com/thunder/thunder-distribution',
    docsDir: 'docs',
    docsBranch: '6.3.x',
    contributors: false,
    navbar: [
      {
        text: 'User Guide',
        link: '/user-guide/feature-overview.html',
      },
      {
        text: 'Developer Guide',
        link: '/developer-guide/setup.md',
      },
      {
        text: 'Contribute',
        link: '../../contributing.md',
      },
      {
        text: 'Thunder.org',
        link: 'https://thunder.org',
      },
    ],
    sidebar: {
      '/user-guide/': [
        '/user-guide/feature-overview.md'
      ],
      '/developer-guide/': [
        '/developer-guide/setup.md',
        {
          text: 'Operating',
          children: [
            '/developer-guide/operating/varnish.md',
          ],
        },
        '/developer-guide/testing.md',
        '/developer-guide/headless.md',
        '/developer-guide/paragraphs_paste.md',
        {
          text: 'Migration',
          children: [
            '/developer-guide/migration/migrate-3-6.md',
            '/developer-guide/migration/migrate-2-3.md',
          ],
        },
        {
          text: 'Changelogs',
          children: [
            '/changelog/6.3.x',
            '/changelog/6.2.x',
            '/changelog/6.1.x',
            '/changelog/6.0.x',
          ],
        },

      ],
    }
  },
  plugins: [
    [
      '@vuepress/plugin-search',
      {
        // exclude the homepage
        isSearchable: (page) => page.path !== '/',
        getExtraFields: (page) => page.frontmatter.tags ?? [],
      },
    ],
  ],
  async onInitialized(app) {
    const rp = require('request-promise');
    const {createPage} = require("@vuepress/core");
    const logs = [
      {url: 'https://raw.githubusercontent.com/thunder/thunder-distribution/6.0.x/CHANGELOG.md', title: 'Changelog 6.0.x', path: '/changelog/6.0.x'},
      {url: 'https://raw.githubusercontent.com/thunder/thunder-distribution/6.1.x/CHANGELOG.md', title: 'Changelog 6.1.x', path: '/changelog/6.1.x'},
      {url: 'https://raw.githubusercontent.com/thunder/thunder-distribution/6.2.x/CHANGELOG.md', title: 'Changelog 6.2.x', path: '/changelog/6.2.x'},
      {url: 'https://raw.githubusercontent.com/thunder/thunder-distribution/6.3.x/CHANGELOG.md', title: 'Changelog 6.3.x', path: '/changelog/6.3.x'},
    ]
    await Promise.all(logs.map(async (log) => {
      const content = await rp(log.url);
      const page = await createPage(app, {
        path: log.path,
        frontmatter: {
          layout: 'Layout',
          sidebar: 'auto',
          title: log.title
        },
        content
      })
      app.pages.push(page)
    }));
  }
}
