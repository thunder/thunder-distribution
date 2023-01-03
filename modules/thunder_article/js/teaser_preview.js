((Drupal) => {
  /**
   * Define teaser preview template.
   *
   * @param {object} config
   *   Configuration options for teaser preview template.
   *
   * @return {string}
   *   Returns markup string for teaser preview.
   */
  Drupal.theme.thunderArticleTeaserPreview = (config) => {
    return (
      `<img src="${config.image}"/>` +
      `<h1>${config.title}</h1>` +
      `<p>${config.text}</p>`
    );
  };

  Drupal.behaviors.teaserPreview = {
    attach() {
      const title = document.querySelector(
        '[data-drupal-selector="edit-field-seo-title-0-value"]',
      );
      const text = document.querySelector(
        '[data-drupal-selector="edit-field-teaser-text-0-value"]',
      );

      title.addEventListener('input', (e) => {
        const titleField = document.querySelector('article.teaser-preview h1');
        titleField.textContent = this.trimText(e.target.value, 100);
      });

      text.addEventListener('input', (e) => {
        const textField = document.querySelector('article.teaser-preview p');
        textField.innerHTML = this.trimText(e.target.value, 155).replace(
          /(?:\r\n|\r|\n)/g,
          '<br>',
        );
      });

      let imageSrc = '';
      const imageField = document.querySelector(
        '[data-drupal-selector="edit-field-teaser-media-wrapper"] img',
      );
      if (imageField) {
        imageSrc = imageField.src;
      }

      const container = document.querySelector('article.teaser-preview');
      container.innerHTML = Drupal.theme('thunderArticleTeaserPreview', {
        image: imageSrc,
        title: this.trimText(title.value, 100),
        text: this.trimText(text.value, 155).replace(/(?:\r\n|\r|\n)/g, '<br>'),
      });
    },

    trimText: (string, length) => {
      return string.length > length
        ? `${string.substring(0, length - 3)}...`
        : string;
    },
  };
})(Drupal);
