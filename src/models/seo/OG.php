<?php declare(strict_types=1);

namespace alanrogers\tools\models\seo;

use craft\helpers\Html;
use Override;

class OG extends Container
{
    /**
     * The title of your object as it should appear within the graph.
     * @var string
     */
    private string $title = '';

    /**
     * A one to two sentence description of your object.
     * @var string
     */
    private string $description = '';

    /**
     * If your object is part of a larger web site, the name which should be displayed for the overall site.
     * @var string
     */
    private string $site_name = '';

    /**
     * The type of your object, e.g., "video.movie".
     * Depending on the type you specify, other properties may also be required.
     * @var string
     */
    private string $type = '';

    /**
     * Of the current page - should be canonical in most cases
     * @var string
     */
    private string $url = '';

    /**
     * URL
     * @var string[]
     */
    private array $image = [];

    /**
     * URL
     * @var string[]
     */
    private array $video = [];

    /**
     * The word that appears before this object's title in a sentence. An enum of (a, an, the, "", auto). If auto is
     * chosen, the consumer of your data should chose between "a" or "an". Default is "" (blank).
     * @var string
     */
    private string $determiner = '';

    /**
     * The locale these tags are marked up in.
     * @var string
     */
    private string $locale = '';

    /**
     * Custom tags and their values to add.
     * @var array
     */
    private array $_custom_types = [];


    #[Override]
    public function fields(): array
    {
        return [
            'title',
            'description',
            'site_name',
            'type',
            'url',
            'image',
            'video',
            'determiner',
            'locale',
            '_custom',
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'title',
            'description',
            'site_name',
            'type',
            'url',
            'image',
            'video',
            'determiner',
            'locale',
            '_custom',
        ];
    }

    #[Override]
    public function render(): string
    {
        $html = [];

        if ($this->locale) {
            $html[] = '<meta property="og:locale" content="' . Html::encode($this->locale) . '">';
        }
        if ($this->title) {
            $html[] = '<meta property="og:title" content="' . Html::encode($this->title) . '">';
        }
        if ($this->description) {
            $html[] = '<meta property="og:description" content="' . Html::encode($this->description) . '">';
        }
        if ($this->site_name) {
            $html[] = '<meta property="og:site_name" content="' . Html::encode($this->site_name) . '">';
        }

        if ($this->type) {
            $html[] = '<meta property="og:type" content="' . Html::encode($this->type) . '">';
        }
        foreach ($this->_custom_types as $namespace => $custom) {
            foreach ($custom as $name => $value) {
                $html[] = '<meta property="og:type" content="' . Html::encode($namespace) . ':' . Html::encode($name) . ':' . Html::encode($value) . '">';
            }
        }
        if ($this->determiner) {
            $html[] = '<meta property="og:determiner" content="' . Html::encode($this->determiner) . '">';
        }

        if ($this->url) {
            $html[] = '<meta property="og:url" content="' . Html::encode($this->url) . '">';
        }
        foreach ($this->image as $image) {
            if (is_string($image)) {
                $html[] = '<meta property="og:image" content="' . Html::encode($image) . '">';
            } else { // is array
                foreach ($image as $key => $value) {
                    $html[] = '<meta property="og:image:' . Html::encode($key) . '" content="' . Html::encode($value) . '">';
                }
            }
        }
        foreach ($this->video as $video) {
            if (is_string($video)) {
                $html[] = '<meta property="og:video" content="' . Html::encode($video) . '">';
            } else {
                foreach ($video as $key => $value) {
                    $html[] = '<meta property="og:video:' . Html::encode($key) . '" content="' . Html::encode($value) . '">';
                }
            }
        }

        return implode("\n", $html);
    }

    #[Override]
    public function headers(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getSiteName(): string
    {
        return $this->site_name;
    }

    public function setSiteName(string $site_name): static
    {
        $this->site_name = $site_name;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getImage(): array
    {
        return $this->image;
    }

    /**
     * @param string $image_url
     * @param array $properties Extra meta data properties for the image
     * @return $this
     */
    public function addImage(string $image_url, array $properties=[]): static
    {
        $this->image[] = $image_url;
        if ($properties) {
            $this->image[] = [ ...$properties ];
        }
        return $this;
    }

    public function getVideo(): array
    {
        return $this->video;
    }

    /**
     * @param string $video_url
     * @param array $properties Extra meta data properties for the video
     * @return $this
     */
    public function addVideo(string $video_url, array $properties=[]): static
    {
        $this->video[] = $video_url;
        if ($properties) {
            $this->video[] = [ ...$properties ];
        }
        return $this;
    }

    public function getDeterminer(): string
    {
        return $this->determiner;
    }

    public function setDeterminer(string $determiner): static
    {
        $this->determiner = $determiner;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function addCustomTypeData(string $namespace, string $name, mixed $value): static
    {
        $this->_custom_types[$namespace][$name] = $value;
        return $this;
    }

    public function clearCustomTypeNamespace(string $namespace, ?string $name=null): static
    {
        if ($name !== null) {
            unset($this->_custom_types[$namespace][$name]);
        } else {
            unset($this->_custom_types[$namespace]);
        }
        return $this;
    }
}