# Changelog

## [8.4.3](https://github.com/thunder/thunder-distribution/tree/8.4.3) 2026-08-31

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.4.2...8.4.3)

- Remove the hidden `field_digital_source_type` entry from the image media form displays in the
  `thunder_post_update_0007_add_ai_fields_to_image_media` update, so the field is not duplicated on
  sites that already had it hidden before the update ran.

## [8.4.2](https://github.com/thunder/thunder-distribution/tree/8.4.2) 2026-08-31

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.4.1...8.4.2)

- Added an "AI disclosure" field to image media, based on the IPTC Photo Metadata "Digital Source Type"
  vocabulary ("Created with AI" / "Edited with AI").
- The disclosure is embedded into the image file as `XMP-iptcExt:DigitalSourceType` on save, and an existing
  disclosure in an uploaded file is adopted automatically. This requires the `exiftool` binary on the server;
  without it media still saves and a warning is shown on the status report.
- Added the `ai_disclosure_upload_only` and `ai_disclosure_auto_detect` settings to the Thunder Media
  configuration form.
- Exposed `digitalSourceType` on image media in the GraphQL schema.

## [8.4.1](https://github.com/thunder/thunder-distribution/tree/8.4.1) 2026-08-13

[Full Changelog](https://github.com/thunder/thunder-distribution/compare/8.4.0...8.4.1)

- Update Diff to 2.x and require Media Library Media Modify ^2.0.0-beta2 and Paragraphs ^1.22.

## [8.4.0](https://github.com/thunder/thunder-distribution/tree/8.4.0) 2026-07-02

Support Drupal 11.4.
