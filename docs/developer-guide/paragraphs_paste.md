# Paragraphs Paste

Paragraph paste is a module that enables editors to use copy and paste to create multiple paragraphs from pre-written
content.

To accommodate various paragraph types available in a project, it is possible to map different types of data to their
relevant paragraph types by changing the configuration on the form display settings for paragraphs fields, f.e.
`admin/structure/types/manage/article/form-display`. Set a property path in the pattern of
`{entity_type}.{bundle}.{field_name}` or
`{entity_type}.{bundle}.{entity_reference_field_name}:{referenced_entity_bundle}.{field_name}` for the plugin to map to
the paragraphs source field.

To support inserting content into paragraph types containing multiple fields, it is possible to create custom paragraphs
paste plugins. Implement the `ParagraphsPastePluginInterface` by extending the `ParagraphsPastePluginBase`class.
First provide an `isApplicable()` method to determine for which type of data the plugin should, usually this would
consist of grepping for some kind of keyword on the passed data. Next, implement the `createParagraphEntity()` method
to create the desired paragraph entity and fill its fields with a processed value from the passed data.

Here is an example for a paragraph type with additional headline fields.

```php
<?php

namespace Drupal\custom\Plugin\ParagraphsPastePlugin;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin\Textile;

/**
 * Defines a paragraphs_paste plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "custom_text",
 *   label = @Translation("Custom text"),
 *   module = "custom",
 *   weight = 0,
 *   allowed_field_types = {"text", "text_long", "text_with_summary", "string",
 *   "string_long"}
 * )
 */
class CustomText extends Textile {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input, array $definition) {

    return (bool) preg_match('~^InsertCustomText~', trim($input));
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraphEntity($input) {
    $property_path = explode('.', $this->configuration['property_path']);

    $target_entity_type = array_shift($property_path);
    $target_bundle = array_shift($property_path);

    $entity_type = $this->entityTypeManager->getDefinition($target_entity_type);

    $paragraph_entity = $this->entityTypeManager->getStorage($target_entity_type)
      ->create([
        $entity_type->getKey('bundle') => $target_bundle,
      ]);

    $lines = preg_split('~((\r?\n){2,})~', $input);
    $out = [];
    foreach ($lines as $line) {
      if (preg_match('~^InsertCustomText.*~', $line)) {
        $this->setFieldValue($paragraph_entity, ['field_headline'], substr($line, 18));
      }
      elseif (preg_match('~^[hH][23]~', $line)) {
        // Move first headline to dedicated fields.
        if (empty($out)) {
          $this->setFieldValue($paragraph_entity, ['field_another_headline'], substr($line, 3));
        }
        else {
          $out[] = $line;
        }
      }
      else {
        $out[] = $line;
      }
    }
    $this->setFieldValue($paragraph_entity, $property_path, $this->parseTextileInput(implode("\n\n", $out)));

    return $paragraph_entity;
  }

}
```
