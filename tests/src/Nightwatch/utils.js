// eslint-disable-next-line import/no-dynamic-require
const request = require(`${process.cwd()}/node_modules/request`);

module.exports = {
  setSiteInfo(adminUser, adminPass, queryParams, browser, browserDoneCallback) {
    const baseUrl = process.env.DRUPAL_TEST_BASE_URL;

    request(
      {
        url: `${baseUrl}/user/login?_format=json`,
        method: "POST",
        body: JSON.stringify({ name: "test-admin", pass: "test-admin" }),
        headers: {
          "Content-type": "application/json"
        },
        jar: true
      },
      () => {
        request(
          {
            url: `${baseUrl}/thunder-performance-measurement/site-info`,
            qs: queryParams,
            jar: true
          },
          (error, response, body) => {
            // Get Site information first!!!
            const { data } = JSON.parse(body);

            browser._site_info = data;

            browserDoneCallback();
          }
        );
      }
    );
  }
};
