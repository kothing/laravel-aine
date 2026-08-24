<?php

namespace App\Aine;

use App\Models\Collection;
use App\Models\CollectionField;

/**
 * Preset project templates.
 *
 * Each template is a list of collections with their fields. Relation fields
 * reference other collections by slug with a {{slug}} placeholder that is
 * resolved to the real collection id when the template is applied.
 *
 * Used by both ProjectsController@store (Create New Project) and the demo
 * seeder, so the admin-created projects and the preset data always match.
 */
class ProjectTemplates
{
    public const CMS = 2;
    public const BUSINESS_DIRECTORY = 3;

    /**
     * All available templates: type => definition.
     */
    public static function all(): array
    {
        return [
            self::CMS => [
                'name' => 'CMS Template',
                'collections' => [
                    [
                        'name' => 'Pages', 'slug' => 'pages', 'order' => 1,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Title', 'name' => 'title', 'options' => '{"slug": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false, "message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 2, 'type' => 'slug', 'label' => 'Path', 'name' => 'url', 'options' => '{"slug": {"field": "title"},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 3, 'type' => 'richtext', 'label' => 'Content', 'name' => 'content', 'options' => '{"slug": [],"media": [], "relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Articles', 'slug' => 'articles', 'order' => 2,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Title', 'name' => 'title', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 2, 'type' => 'slug', 'label' => 'Path', 'name' => 'url', 'options' => '{"slug": {"field": "title"},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 3, 'type' => 'longtext', 'label' => 'Excerpt', 'name' => 'excerpt', 'options' => '{"slug": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 4, 'type' => 'richtext', 'label' => 'Content', 'name' => 'content', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 5, 'type' => 'media', 'label' => 'Featured Image', 'name' => 'featured-image', 'options' => '{"slug": [],"media": {"type": 1},"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 6, 'type' => 'relation', 'label' => 'Category', 'name' => 'category', 'options' => '{"slug": [],"media": [],"relation": {"type": "1","collection": "{{categories}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 7, 'type' => 'relation', 'label' => 'Author', 'name' => 'author', 'options' => '{"slug": [],"relation": {"type": 1,"collection": "{{authors}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 8, 'type' => 'relation', 'label' => 'Tags', 'name' => 'tags', 'options' => '{"slug": [],"media": [],"relation": {"type": "2","collection": "{{tags}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 9, 'type' => 'boolean', 'label' => 'Slider', 'name' => 'slider', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 10, 'type' => 'boolean', 'label' => 'Featured', 'name' => 'featured', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 11, 'type' => 'boolean', 'label' => 'Recommended', 'name' => 'recommended', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Categories', 'slug' => 'categories', 'order' => 3,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Title', 'name' => 'title', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'slug', 'label' => 'Path', 'name' => 'url', 'options' => '{"slug": {"field": "title"},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Authors', 'slug' => 'authors', 'order' => 4,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Name', 'name' => 'name', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'longtext', 'label' => 'Info', 'name' => 'info', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'media', 'label' => 'Avatar', 'name' => 'avatar', 'options' => '{"slug": [],"media": {"type": 1},"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'text', 'label' => 'Facebook', 'name' => 'facebook', 'options' => '{"slug": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'text', 'label' => 'Instagram', 'name' => 'instagram', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'text', 'label' => 'Twitter', 'name' => 'twitter', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'text', 'label' => 'Linkedin', 'name' => 'linkedin', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Tags', 'slug' => 'tags', 'order' => 5,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Tag', 'name' => 'tag', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Comments', 'slug' => 'comments', 'order' => 6,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Name', 'name' => 'name', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'email', 'label' => 'E-mail', 'name' => 'e-mail', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'longtext', 'label' => 'Comment', 'name' => 'comment', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'relation', 'label' => 'Article', 'name' => 'article', 'options' => '{"slug": [],"relation": {"type": 1,"collection": "{{articles}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Globals', 'slug' => 'globals', 'order' => 7,
                        'fields' => [
                            ['order' => 1, 'type' => 'slug', 'label' => 'Label', 'name' => 'label', 'options' => '{"slug": {"field": null},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'text', 'label' => 'Value', 'name' => 'value', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                ],
            ],

            self::BUSINESS_DIRECTORY => [
                'name' => 'Business Directory',
                'collections' => [
                    [
                        'name' => 'Listings', 'slug' => 'listings', 'order' => 1,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Business Name', 'name' => 'title', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 2, 'type' => 'slug', 'label' => 'Path', 'name' => 'url', 'options' => '{"slug": {"field": "title"},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 3, 'type' => 'longtext', 'label' => 'Description', 'name' => 'description', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 4, 'type' => 'relation', 'label' => 'Category', 'name' => 'category', 'options' => '{"slug": [],"media": [],"relation": {"type": "1","collection": "{{categories}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 5, 'type' => 'relation', 'label' => 'Tags', 'name' => 'tags', 'options' => '{"slug": [],"media": [],"relation": {"type": "2","collection": "{{tags}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 6, 'type' => 'relation', 'label' => 'Location', 'name' => 'location', 'options' => '{"slug": [],"media": [],"relation": {"type": "1","collection": "{{locations}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 7, 'type' => 'media', 'label' => 'Logo', 'name' => 'logo', 'options' => '{"slug": [],"media": {"type": 1},"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 8, 'type' => 'media', 'label' => 'Gallery', 'name' => 'gallery', 'options' => '{"slug": [],"media": {"type": 2},"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 9, 'type' => 'text', 'label' => 'Phone', 'name' => 'phone', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 10, 'type' => 'email', 'label' => 'Email', 'name' => 'email', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 11, 'type' => 'text', 'label' => 'Website', 'name' => 'website', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 12, 'type' => 'longtext', 'label' => 'Address', 'name' => 'address', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 13, 'type' => 'longtext', 'label' => 'Opening Hours', 'name' => 'opening-hours', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 14, 'type' => 'enumeration', 'label' => 'Price Range', 'name' => 'price-range', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": ["$","$$","$$$","$$$$"],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 15, 'type' => 'boolean', 'label' => 'Featured', 'name' => 'featured', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Categories', 'slug' => 'categories', 'order' => 2,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Title', 'name' => 'title', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'slug', 'label' => 'Path', 'name' => 'url', 'options' => '{"slug": {"field": "title"},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Tags', 'slug' => 'tags', 'order' => 3,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Tag', 'name' => 'tag', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Locations', 'slug' => 'locations', 'order' => 4,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Name', 'name' => 'name', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'slug', 'label' => 'Path', 'name' => 'url', 'options' => '{"slug": {"field": "name"},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Reviews', 'slug' => 'reviews', 'order' => 5,
                        'fields' => [
                            ['order' => 1, 'type' => 'text', 'label' => 'Name', 'name' => 'name', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'number', 'label' => 'Rating', 'name' => 'rating', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'longtext', 'label' => 'Review', 'name' => 'review', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": false,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'relation', 'label' => 'Listing', 'name' => 'listing', 'options' => '{"slug": [],"relation": {"type": 1,"collection": "{{listings}}"},"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                    [
                        'name' => 'Globals', 'slug' => 'globals', 'order' => 6,
                        'fields' => [
                            ['order' => 1, 'type' => 'slug', 'label' => 'Label', 'name' => 'label', 'options' => '{"slug": {"field": null},"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": true,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                            ['order' => 1, 'type' => 'text', 'label' => 'Value', 'name' => 'value', 'options' => '{"slug": [],"media": [],"relation": [],"enumeration": [],"hideInContentList": false}', 'validations' => '{"unique": {"status": false,"message": null},"required": {"status": true,"message": null},"charcount": {"max": null,"min": null,"type": "Between","status": false,"message": null}}'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get one template definition by type.
     */
    public static function get(int $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /**
     * Apply a template to a project: create all collections and fields,
     * resolving relation placeholders ({{slug}}) to real collection ids.
     *
     * @param \App\Models\Project $project
     * @param array               $template
     * @return array slug => Collection
     */
    public static function apply($project, array $template): array
    {
        $collections = [];

        foreach ($template['collections'] as $collectionDef) {
            $collections[$collectionDef['slug']] = Collection::create([
                'name' => $collectionDef['name'],
                'slug' => $collectionDef['slug'],
                'project_id' => $project->id,
                'order' => $collectionDef['order'],
            ]);
        }

        foreach ($template['collections'] as $collectionDef) {
            $collection = $collections[$collectionDef['slug']];

            foreach ($collectionDef['fields'] as $fieldDef) {
                $options = str_replace(
                    array_map(fn ($slug) => '{{'.$slug.'}}', array_keys($collections)),
                    array_map(fn ($c) => $c->id, $collections),
                    $fieldDef['options']
                );

                CollectionField::create([
                    'project_id' => $project->id,
                    'collection_id' => $collection->id,
                    'order' => $fieldDef['order'],
                    'type' => $fieldDef['type'],
                    'label' => $fieldDef['label'],
                    'name' => $fieldDef['name'],
                    'options' => $options,
                    'validations' => $fieldDef['validations'],
                ]);
            }
        }

        return $collections;
    }
}
