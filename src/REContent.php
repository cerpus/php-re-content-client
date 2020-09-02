<?php


namespace Cerpus\REContentClient;


use Carbon\Carbon;
use Cerpus\REContentClient\Exceptions\MissingDataException;
use Illuminate\Support\Str;

class REContent
{
    private $id = null;
    private $title = null;
    private $content = null;
    private $desctiption = null;
    private $last_updated_at = null;
    private $tags = null;
    private $type = null;
    private $license = null;
    private $previous_version = null;

    public function generatePayload(): array
    {
        $payload = [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "content" => $this->getContent(),
            "description" => $this->getDesctiption(),
            "last_updated_at" => $this->getLastUpdatedAt(),
            "tags" => $this->getTags(),
            "type" => $this->getType(),
            "previous_version" => $this->getPreviousVersion(),
            "license" => $this->getLicense(),
        ];

        $this->verifyPayload($payload);

        return $this->cleanUpPayload($payload);
    }

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

    protected function cleanString($string)
    {
        if (!$string) {
            return $string;
        }

        return trim(strip_tags(html_entity_decode($string)));
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

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content = null): void
    {
        if (empty($content)) {
            $this->content = null;
            return;
        }

        $this->content = $this->cleanString($content);

        if (empty($this->content)) {
            $this->content = null;
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

    public function getLastUpdatedAt()
    {
        return $this->last_updated_at;
    }

    public function setLastUpdatedAt($updatedAt = null)
    {
        if ($updatedAt instanceof Carbon) {
            $updatedAt = $updatedAt->timestamp;
        }

        $this->last_updated_at = $updatedAt;
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
        $optionalFields = ["description", "content", "tags", "previous_version", "last_updated_at"];

        foreach ($optionalFields as $field) {
            if (empty($payload[$field])) {
                unset($payload[$field]);
            }
        }

        return $payload;
    }

}
