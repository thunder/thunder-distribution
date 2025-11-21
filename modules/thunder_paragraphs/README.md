# Thunder paragraphs

Paragraphs basically represents the content part of your article. They give editors the freedom to structure the content
as they want. These module contains all our predefined paragraph types, which can be used out-of-the-box.

Currently there are:

- Gallery
- Media (Image/Video)
- Instagram
- Twitter
- Quote
- Text
- Link

## Gallery

Create a gallery by giving a name and select or upload some images. Galleries will be displayed as sliding images.

## Media

Include an image or a video where ever you want in your content. Just select an existing from the Media-Browser or
upload a new one.

## Instagram

An Instagram link is everything you need for this paragraph. Thunder will do the rest for you.

## Twitter

Same as instagram, just for Twitter.

## Text

The text paragraph just contains a large WYSIWYG text field.

## Quote

Same as text paragraph. The difference is it will be displayed as a quote in the frontend.

## Link

Lets you add a list of internal or external links to your article.

## Make drupal paragraph-click-to-open on edit pages with selector configuration

To make the paragraph-click-to-open module work with Thunder Paragraphs, you need to add the following configuration to your
`drupal file: at '/admin/config/content/thunder-paragraphs'`:

```
selector: '.paragraph-form-item--has-preview, [id^="field-paragraphs-"][id*="-item-wrapper"]'

you can also provide additional CSS selectors if you have custom paragraph types.
```
