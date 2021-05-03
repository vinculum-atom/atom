<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Update static page content for unchanged home and about pages.
 *
 * @package    AccesstoMemory
 * @subpackage migration
 */
class arMigration0191
{
    const VERSION = 191;
    const MIN_MILESTONE = 2;

    public function up($configuration)
    {
        $pageTranslationsToUpdate = [
            ['slug' => 'home', 'culture' => 'de', 'md5' => '04d25d84d1786e22a8c59f463c273501'],
            ['slug' => 'home', 'culture' => 'en', 'md5' => '580b4a598046009e96f735009463d428'],
            ['slug' => 'home', 'culture' => 'es', 'md5' => 'b04a3f05604c0e1afe1fb09a649074b8'],
            ['slug' => 'home', 'culture' => 'fr', 'md5' => '638db88e1339f84cf1e6343a55a92083'],
            ['slug' => 'home', 'culture' => 'gl', 'md5' => 'abf8594d97831748fcd229a2e27d1b48'],
            ['slug' => 'home', 'culture' => 'id', 'md5' => 'a2f70bbe40790929120a8b53b9ce0931'],
            ['slug' => 'home', 'culture' => 'it', 'md5' => 'da9f37c97c6de9c3373e75d37252fbad'],
            ['slug' => 'home', 'culture' => 'ja', 'md5' => '6323128790ffa6660f76a0ce56e70045'],
            ['slug' => 'home', 'culture' => 'ka', 'md5' => '0afa86ca4c0e1dcef22fbb5f71b653a2'],
            ['slug' => 'home', 'culture' => 'ko', 'md5' => '37878a88769daa72062121a91a2150a1'],
            ['slug' => 'home', 'culture' => 'nl', 'md5' => '9c127e0b9941a3c2c56145d0c99a1aac'],
            ['slug' => 'home', 'culture' => 'pl', 'md5' => 'e5c36ee4ef09e183cffead326e4f3569'],
            ['slug' => 'home', 'culture' => 'pt', 'md5' => '8c393dc22423f78ce819f527dfa66623'],
            ['slug' => 'home', 'culture' => 'sl', 'md5' => '0acf1deeef01cdb893ea637a5006569b'],
            ['slug' => 'home', 'culture' => 'th', 'md5' => 'e276345d6eff9684c70818a62a10d15f'],
            ['slug' => 'about', 'culture' => 'de', 'md5' => '9a6ff67dcbd372535d10279b752cf57e'],
            ['slug' => 'about', 'culture' => 'en', 'md5' => '417289e5502bcda491360e022acbf089'],
            ['slug' => 'about', 'culture' => 'es', 'md5' => '6fc8a228dca898c724de85d3207435c0'],
            ['slug' => 'about', 'culture' => 'fr', 'md5' => 'd3edafeb161a509a2b7a4f087627fe9a'],
            ['slug' => 'about', 'culture' => 'it', 'md5' => 'fcdf7b45dce0224854d12a62cf536397'],
            ['slug' => 'about', 'culture' => 'ko', 'md5' => '725d8062c09d45d734a9cda0e83012e5'],
            ['slug' => 'about', 'culture' => 'nl', 'md5' => 'c13dfbf0e6d35c484bdce3b7022c8362'],
            ['slug' => 'about', 'culture' => 'pt', 'md5' => 'ab41ad0d560257ee99f41ac15706b569'],
            ['slug' => 'about', 'culture' => 'sl', 'md5' => '400b0638d451050439735047928724f0'],
        ];

        // Determine the slugs of all pages that need to be updated
        $pageSlugs = array_unique(array_column($pageTranslationsToUpdate, 'slug'));

        // For each page that needs to be updated get simplified version of its content
        $newPageContent = $this->parseStaticPageFixtureData($pageSlugs);

        // Cache of static page IDs
        $pageIds = [];

        // Try to update each translation
        foreach ($pageTranslationsToUpdate as $translation) {
            $slug = $translation['slug'];

            // Look up static page ID if necessary
            if (empty($pageIds[$slug])) {
                $criteria = new Criteria();
                $criteria->add(QubitSlug::SLUG, $slug);
                $criteria->addJoin(QubitSlug::OBJECT_ID, QubitStaticPage::ID);

                $page = QubitStaticPage::getOne($criteria);
                $pageIds[$slug] = $page->id;
            }

            // Fetch static page translation
            $culture = $translation['culture'];

            $criteria = new Criteria();
            $criteria->add(QubitStaticPageI18n::ID, $pageIds[$slug]);
            $criteria->add(QubitStaticPageI18n::CULTURE, $culture);

            $page = QubitStaticPageI18n::getOne($criteria);

            // Attempt to update static page's title and content if they've never been changed
            if ($translation['md5'] == md5($page->title.$page->content)) {
                $pageTranslation = $newPageContent[$slug][$culture];

                // Only update static page if both a title and content are specified in the fixture
                if (!empty($pageTranslation['title']) && !empty($pageTranslation['content'])) {
                    $page->title = $pageTranslation['title'];
                    $page->content = $pageTranslation['content'];
                    $page->save();
                }
            }
        }

        return true;
    }

    private function parseStaticPageFixtureData($pageSlugs)
    {
        $staticPageData = sfYaml::load('data/fixtures/staticPages.yml');
        $parseStaticPageFixtureData = $staticPageData['QubitStaticPage'];

        // Example: ['about' => ['en' => ['title' => 'Title', 'content' => 'Content']]];
        $simplified = [];

        foreach ($parseStaticPageFixtureData as $definition) {
            $slug = $definition['slug'];

            if (in_array($slug, $pageSlugs)) {
                if (empty($simplified[$slug])) {
                    $simplified[$slug] = [];
                }

                foreach ($definition['title'] as $culture => $title) {
                    $simplified[$slug][$culture] = ['title' => $title];
                }

                foreach ($definition['content'] as $culture => $content) {
                    $simplified[$slug][$culture]['content'] = $content;
                }
            }
        }

        return $simplified;
    }
}
