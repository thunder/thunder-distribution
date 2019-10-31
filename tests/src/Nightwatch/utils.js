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

      // Filter custom sub-from fields.
      ["target_type_distribution", "fields", "inline_entity_form"].forEach(
        filterKey => {
          if (typeof data[key][filterKey] === "object") {
            result[key][filterKey] = this.filterObject(
              data[key][filterKey],
              filterBy[key]
            );
          }
        }
      );

      return result;
    }, {});
  }
};
