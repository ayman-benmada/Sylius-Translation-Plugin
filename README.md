<h1>Sylius Translation Plugin</h1>

<p>
    The plugin enhances the <a href="https://github.com/lexik/LexikTranslationBundle">Lexik translation bundle</a> by incorporating a channel-based translation feature and adapting the visual appearance to the back-office theme.
</p>

## Details

The displayed locales depend on your channel's configuration. In this example, we have selected French and English.

![presentation photo](https://github.com/ayman-benmada/Sylius-Translation-Plugin/blob/main/src/Resources/public/image/presentation-1.png?raw=true)

That's why only these two languages are displayed for this channel.

![presentation photo](https://github.com/ayman-benmada/Sylius-Translation-Plugin/blob/main/src/Resources/public/image/presentation-2.png?raw=true)

You also have a preview page to visualize the number of missing translations for each locale.

![presentation photo](https://github.com/ayman-benmada/Sylius-Translation-Plugin/blob/main/src/Resources/public/image/presentation-3.png?raw=true)

## Important

- The priority of the content to display is as follows: first, the **channel-specific translation**, then, if not provided, the **global translation stored in your database**, and finally, if none are available, the **translation in your translation file**.
- You can manage all translations for your channels on a single page. When you switch between channels, the locales defined for this channel will be displayed.

## Installation

⚠️ Make sure you don't have **ONLY_FULL_GROUP_BY** enabled in your MySQL mode, otherwise remove it! To check the mode, execute the following SQL query: ```SELECT @@sql_mode```

Require plugin with composer :

```bash
composer require abenmada/sylius-translation-plugin
```

Change your `config/bundles.php` file to add the line for the plugin :

```php
<?php

return [
    //..
    Lexik\Bundle\TranslationBundle\LexikTranslationBundle::class => ['all' => true],
    Abenmada\TranslationPlugin\TranslationPlugin::class => ['all' => true],
]
```

Then create the config file in `config/packages/abenmada_translation_plugin.yaml` :

```yaml
imports:
    - { resource: "@TranslationPlugin/Resources/config/services.yaml" }
```

Then import the routes in `config/routes/abenmada_translation_plugin.yaml` :

```yaml
abenmada_translation_plugin_routing:
    resource: "@TranslationPlugin/Resources/config/routes.yaml"
    prefix: /%sylius_admin.path_name%/translations
```

Update the entity `src/Entity/Channel/Channel.php` :

```php
<?php

declare(strict_types=1);

namespace App\Entity\Channel;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Abenmada\TranslationPlugin\Model\Channel\ChannelTrait as AbenmadaTranslationChannelTrait;
use Sylius\Component\Core\Model\Channel as BaseChannel;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_channel")
 */
class Channel extends BaseChannel
{
    use AbenmadaTranslationChannelTrait;

    public function __construct()
    {
        $this->channelTranslations = new ArrayCollection();
        parent::__construct();
    }
}
```

Run the migration :
```bash
bin/console doctrine:migration:migrate
```

Install the assets :

```bash
bin/console assets:install --ansi
```

import translations files content into your database :

```bash
bin/console lexik:translations:import
```

## Managing locales

- The default supported locales are: English (en), French (fr), Arabic (ar), German (de), Spanish (es), Polish (pl), Portuguese (pt), and Italian (it).
- You can adjust your language settings through the provided configuration options :

```yaml
lexik_translation:
    fallback_locale: [ '%locale%' ]                     # default locale(s) to use
    managed_locales: [ en, fr, ar, de, es, pl, pt, it ] # locale(s) that the bundle has to manage
```
