<h1>Translation plugin</h1>

<p>
    The plugin enhances the <a href="https://github.com/lexik/LexikTranslationBundle">Lexik translation bundle</a> by incorporating a channel-based translation feature and adapting the visual appearance to the back-office theme.
</p>

## Installation

Add in `composer.json`

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "plugin/TranslationPlugin",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "abenmada/translation-plugin": "@dev"
    }
}
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
    prefix: /admin/translations
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

To import translations files content into your database :

```bash
bin/console lexik:translations:import
```
