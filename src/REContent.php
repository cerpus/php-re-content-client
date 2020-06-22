<?php


namespace Cerpus\REContentClient;


use Cerpus\REContentClient\Exceptions\MissingDataException;
use Illuminate\Support\Str;

class REContent
{
    private $id = null;
    private $title = null;
    private $desctiption = null;
    private $tags = null;
    private $type = null;
    private $license = null;
    private $previous_version = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string|null  $id
     */
    public function setId($id): void
    {
        if (empty($id)) {
            $this->id = null;
            return;
        }

        $this->id = $this->cleanString($id);

        if (empty($this->id)) {
            $this->id = null;
        }
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param  mixed  $title
     */
    public function setTitle($title): void
    {
        if (empty($title)) {
            $this->title = null;
            return;
        }

        $this->title = $this->cleanString($title);

        if (empty($this->title)) {
            $this->title = null;
        }
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  string  $type
     */
    public function setType($type): void
    {
        if (empty($type)) {
            $this->type = null;
            return;
        }

        $this->type = $this->cleanString($type);

        if (empty($this->type)) {
            $this->type = null;
        }
    }

    /**
     * @return string|null
     */
    public function getDesctiption()
    {
        return $this->desctiption;
    }

    /**
     * @param  string|null  $desctiption
     */
    public function setDesctiption($desctiption): void
    {
        if (empty($desctiption)) {
            $this->desctiption = null;
            return;
        }

        $this->desctiption = $this->cleanString($desctiption);

        if (empty($this->desctiption)) {
            $this->desctiption = null;
        }
    }

    /**
     * @return array|null
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param  array  $tags
     */
    public function setTags(array $tags): void
    {
        if (empty($tags)) {
            $this->tags = null;
            return;
        }

        $tags = collect($tags)->map(function ($tag) {
            return Str::slug($this->cleanString($tag));
        })->toArray();

        $this->tags = $tags;

        if (empty($this->tags)) {
            $this->tags = null;
        }
    }

    /**
     * @return string|null
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @param  string  $license
     */
    public function setLicense($license): void
    {
        if (empty($license)) {
            $this->license = null;
            return;
        }

        $this->license = $license;

        if (empty($this->license)) {
            $this->license = null;
        }
    }

    /**
     * @return string|null
     */
    public function getPreviousVersion()
    {
        return $this->previous_version;
    }

    /**
     * @param  string|null  $previousVersion
     */
    public function setPreviousVersion($previousVersion): void
    {
        if (empty($previousVersion)) {
            $this->previous_version = null;
            return;
        }

        $this->previous_version = $previousVersion;

        if (empty($this->previous_version)) {
            $this->previous_version = null;
        }
    }


    public function generatePayload(): array
    {
        $payload = [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "description" => $this->getDesctiption(),
            "tags" => $this->getTags(),
            "type" => $this->getType(),
            "previous_version" => $this->getPreviousVersion(),
            "license" => $this->getLicense(),
        ];

        $this->verifyPayload($payload);

        return $this->cleanUpPayload($payload);
    }

    /**
     * @throws MissingDataException
     */
    protected function verifyPayload($payload)
    {
        $requiredFields = ["id", "title", "type", "license"];

        foreach ($requiredFields as $field) {
            if (empty($payload[$field])) {
                throw new MissingDataException("Missing field: $field");
            }
        }
    }

    /**
     * @param  array  $payload
     * @return array
     */
    protected function cleanUpPayload(array $payload): array
    {
        $optionalFields = ["description", "tags", "previous_version"];

        foreach ($optionalFields as $field) {
            if (empty($payload[$field])) {
                unset($payload[$field]);
            }
        }

        return $payload;
    }

    protected function cleanString($string)
    {
        if (!$string) {
            return $string;
        }

        return trim(strip_tags(html_entity_decode($string)));
    }

}
