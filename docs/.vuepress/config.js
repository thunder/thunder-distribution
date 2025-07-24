import { viteBundler } from '@vuepress/bundler-vite'
import { defaultTheme } from '@vuepress/theme-default'
import { defineUserConfig } from 'vuepress'
import { createPage } from '@vuepress/core'
import { searchPlugin } from '@vuepress/plugin-search'
import axios from 'axios'

export default defineUserConfig({
  title: 'Thunder',
  description: 'Thunder is a Drupal distribution for professional publishers.',
  head: [
    ['link', {rel: 'icon', href: '/thunder.svg'}]
  ],
  bundler: viteBundler(),
  theme: defaultTheme({
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
        {
          text: 'Migration',
          children: [
            '/developer-guide/migration/migrate-7-8.md',
            '/developer-guide/migration/migrate-6-7.md',
            '/developer-guide/migration/migrate-3-6.md',
            '/developer-guide/migration/migrate-2-3.md',
          ],
        }
      ],
    }
  }),
  plugins: [
    [
      searchPlugin({
        // exclude the homepage
        isSearchable: (page) => page.path !== '/',
        getExtraFields: (page) => page.frontmatter.tags ?? [],
      }),
    ],
  ]
});
