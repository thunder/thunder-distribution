# Thunder media

## Introduction

Media administration is one of Thunder's main focus. We try to compose all the useful media modules to have a
out-of-the-box solution for media handling. The goal is to make it as easy as possible to create and reuse media
elements.

Currently we cover the following use-cases:

- Multi-Upload of images
- Handle YouTube and Vimeo videos
- Integrate with instagram and twitter cards
- Combining images to a gallery
- Reuse all the objects in different articles

## Configuration

### Instagram

If you would like to have instagram thumbnails in your admin backend, follow
this [documentation](https://github.com/drupal-media/media_entity_instagram#with-instagram-api). You received a
developer key, which must be placed here: /admin/structure/media/manage/instagram.

### Twitter

If you would like to have twitter thumbnails as well in you admin backend, follow
this [documentation](https://github.com/drupal-media/media_entity_twitter#with-twitter-api). You received some keys,
which must be placed here: /admin/structure/media/manage/twitter.

### AI disclosure metadata

Image media items have an "AI disclosure" field, based on the IPTC Photo
Metadata "Digital Source Type" controlled vocabulary, letting editors flag an
image as "Created with AI" or "Edited with AI". When set, the value is
embedded directly into the image file as `XMP-iptcExt:DigitalSourceType`
metadata on save, using the `exiftool` binary.

This requires **`exiftool`** to be installed and available on `PATH` on the
server; it is not bundled with Thunder or installable via Composer. If it is
missing, the media entity still saves normally, a warning is logged, and
`/admin/reports/status` shows a warning until it is installed.

The "Only allow the AI disclosure to be set on upload" option on the
[Thunder Media Configuration form](/admin/config/thunder_media/configuration)
disables the field on the media edit form once the image has already been
uploaded, so editors can only declare AI involvement at upload time and not
change it afterwards.
