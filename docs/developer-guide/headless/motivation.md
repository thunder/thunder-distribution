# Motivation to go with GraphQL 4

Drupal core provides already a turn-key implementation for JSON-API, which basically just needs to be enabled and
configured, and it is good to go. Similarly, version 3 of the GraphQL module is as quickly usable. Both modules expose
all data structures from Drupal as they are.

So why did we manually implement an API? While it is very convenient to have schemas automatically created, it also
leads to an API that is very close to the structure of Drupal. A consumer would have to know the relationships of
entities within Drupal. Especially when working with paragraphs and media entities, you would have to be aware of the
entity references to get to the actual data.
For example, we use media entities for images in paragraphs. The referencing goes unconventionally deep in this case:
If you wanted to get the src attribute of an image in such a paragraph, you would have to dereference
Article => Paragraph => Media Entity => File Entity (src).

Another pain point is, that field names are automatically created. This leads to two separate problems: First, field
names are awkward and again very Drupal specific. In GraphQL 3 we have entityUuid instead of uuid and fieldMyField
instead of just myField.
Second, since field names are automatically generated out of the machine name, the API would change, as soon as you change
the machine name. This sounds not very likely, and for actual fields it should not happen, but sometimes even plugin
names are used to create the schema, and plugins could be exchanged (we had an example of a views-plugin, that was exchanged).

Finally, routing with those automated APIs is very often a process that requires two requests, instead of one.
Usually you just have some URL string, that could be a route to a node, a user, a term or any other entity. To get
the actual data, you will have to do a route query first, to get the information on what kind of entity you are looking at
(plus its ID), and then you would have to do a specific node, term or user query to get the actual page.
