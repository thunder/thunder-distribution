/**
 * Configuration for Thunder Nightwatch tests.
 */

module.exports = {
  createMostUsedContent: {
    testSetFootprint: {
      // Order of test sets is important, because first matched will be used.
      Thunder_Base_Set: [
        ["fields", "field_13", "third_party_settings", "paragraphs_features"]
      ],
      Thunder: [["bundle"]]
    },
    Thunder_Base_Set: {
      fieldsToFill: {
        title: true, // required
        field_2: true, // required
        field_13: {
          bundle_0: {
            field_4: true,
            field_6: true
          },
          bundle_2: {
            field_4: true,
            field_7: true,
            field_12: true
          },
          bundle_4: {
            field_4: true,
            field_12: {
              name: true,
              field_1: true,
              field_2: true
            }
          },
          bundle_8: {
            field_4: true,
            field_7: true
          }
        }, // optional - paragraphs
        field_18: true, // required
        field_23: true // required
      }
    },
    Thunder: {
      fieldsToFill: {
        title: true,
        body: true
      }
    }
  }
};
