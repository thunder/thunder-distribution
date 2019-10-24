// eslint-disable-next-line import/no-dynamic-require
const request = require(`${process.cwd()}/node_modules/request`);

module.exports = {
  setSiteInfo(adminUser, adminPass, queryParams, browser, browserDoneCallback) {
    const baseUrl = process.env.DRUPAL_TEST_BASE_URL;

    request(
      {
        url: `${baseUrl}/user/login?_format=json`,
        method: "POST",
        body: JSON.stringify({ name: adminUser, pass: adminPass }),
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
            const { data } = JSON.parse(body);

            browser._site_info = data;

            browserDoneCallback();
          }
        );
      }
    );
  },

  filterObject(data, filterBy) {
    return Object.keys(data).reduce((result, key) => {
      if (Object.keys(filterBy).includes(key)) {
        result[key] = data[key];
      }

      // Filter nested objects only if we have nested filter definition.
      if (typeof filterBy[key] !== "object") {
        return result;
      }

      if (typeof data[key].target_type_distribution === "object") {
        result[key].target_type_distribution = this.filterObject(
          data[key].target_type_distribution,
          filterBy[key]
        );

        return result;
      }

      if (typeof data[key].fields === "object") {
        result[key].fields = this.filterObject(data[key].fields, filterBy[key]);

        return result;
      }

      return result;
    }, {});
  }
};
