<?php

namespace App;

use App\Entity\NewsArticle;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopImage;
use App\Entity\GithubRelease;
use App\Entity\GithubAlphaBuild;

use URLify;
use \DiscordWebhooks\Embed;
use \DiscordWebhooks\Client;
use Doctrine\ORM\EntityManager;
use ByteUnits\Binary as BinaryFormatter;

use Xenokore\Utility\Helper\StringHelper;

// When APP_ROOT_URL points to a URL that is not publicly available and is not accessible
// by Discord's servers, the images will not be shown. (which is a good thing)
class DiscordNotifier {

    private const COLOR_NEW_WORKSHOP_ITEM = '212121';
    private const COLOR_NEW_NEWS_ARTICLE  = '02f4ec';
    private const COLOR_NEW_ALPHA_PATCH   = 'b402f4';
    private const COLOR_NEW_STABLE_BUILD  = '06f402';

    private EntityManager $em;

    private ?Client $webhook = null;

    public function __construct(
        EntityManager $em,
    ) {
        $this->em = $em;

        if(!empty($_ENV['APP_DISCORD_NOTIFY_WEBHOOK_URL'])){
            $url = (string) $_ENV['APP_DISCORD_NOTIFY_WEBHOOK_URL'];

            // Make sure URL is a valid Discord webhook URL
            if(
                \filter_var($url, FILTER_VALIDATE_URL) === false
                || !StringHelper::startsWith($url, 'https://discord.com/api/webhooks/')
            ) {
                throw new \Exception('invalid discord webhook URL');
            }

            $this->webhook = new Client($url);

            // Set username
            if(!empty($_ENV['APP_DISCORD_NOTIFY_WEBHOOK_USERNAME'])){
                $this->webhook->username((string) $_ENV['APP_DISCORD_NOTIFY_WEBHOOK_USERNAME']);
            }

            // Set avatar
            if(!empty($_ENV['APP_DISCORD_NOTIFY_WEBHOOK_AVATAR'])){
                $this->webhook->avatar((string) $_ENV['APP_DISCORD_NOTIFY_WEBHOOK_AVATAR']);
            }
        }
    }

    public function sendMessage(string $message): bool
    {
        try {
            if($this->webhook === null){
                return false;
            }

            $hook = clone $this->webhook;
            $hook->message($message);
            $hook->send();

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendEmbed(Embed $embed, ?string $message = null): bool
    {
        try {
            if($this->webhook === null){
                return false;
            }

            $hook = clone $this->webhook;
            $hook->embed($embed);

            if(!is_null($message)){
                $hook->message($message);
            }

            $hook->send();

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function notifyNewWorkshopItem(WorkshopItem $item): bool
    {
        if($this->webhook === null){
            return false;
        }

        if($item->getId() === null){
            throw new \Exception("workshop item does not have an ID yet");
        }

        // Create the Embed
        $embed = new Embed();
        $embed->title($item->getName());
        $embed->color(self::COLOR_NEW_WORKSHOP_ITEM);
        $embed->timestamp($item->getCreatedTimestamp()->format('Y-m-d H:i'));
        $embed->url($_ENV['APP_ROOT_URL'] . "/workshop/item/" . $item->getId() . "/" . URLify::slug($item->getName()));

        // Add description
        if($item->getDescription()){
            $description = $item->getDescription();
            if(\strlen($description) > 350) {
                $description = substr($description, 0, 347) . '...';
            }
            $embed->description($description);
        }

        // Add thumbnail
        // For some reason we have to manually get the thumbnail from the DB if the WorkshopItem just got persisted
        $thumbnail = $this->em->getRepository(WorkshopImage::class)->findOneBy(['item' => $item, 'weight' => 0]);
        if($thumbnail){
            $embed->thumbnail($_ENV['APP_ROOT_URL'] . '/workshop/image/' . $item->getId() . '/' . $thumbnail->getFilename());
        } else {
            $embed->thumbnail($_ENV['APP_ROOT_URL'] . '/img/no-image-256.png');
        }

        // Add user
        if($item->getSubmitter()){
            $user = $item->getSubmitter();
            if($user->getAvatar()){
                $embed->footer($user->getUsername(), $_ENV['APP_ROOT_URL'] . '/avatar/' . $user->getAvatar());
            } else {
                $embed->footer($user->getUsername(), $_ENV['APP_ROOT_URL'] . '/img/horny-face-256.png');
            }
        }

        // Send the embed
        return $this->sendEmbed($embed, "New workshop item!");
    }

    public function notifyNewNewsItem(NewsArticle $article): bool
    {
        if($this->webhook === null){
            return false;
        }

        // Create the Embed
        $embed = new Embed();
        $embed->title($article->getTitle());
        $embed->color(self::COLOR_NEW_NEWS_ARTICLE);
        $embed->timestamp($article->getCreatedTimestamp()->format('Y-m-d H:i'));
        $embed->url($_ENV['APP_ROOT_URL'] . '/news/' . $article->getId() . '/' . $article->getCreatedTimestamp()->format('Y-m-d') . '/' . $article->getTitleSlug());
        $embed->footer("KeeperFX Team");

        // Add excerpt
        if($article->getExcerpt()){
            $embed->description($article->getExcerpt());
        } else {
            $embed->description($article->getContents());
        }

        // Send the embed
        return $this->sendEmbed($embed);
    }

    public function notifyNewAlphaPatch(GithubAlphaBuild $alpha_build): bool
    {
        if($this->webhook === null){
            return false;
        }

        // Create description
        // Convert the github issue/PR reference to markdown
        $description = $alpha_build->getWorkflowTitle();
        $replacement = '[#$1](https://github.com/dkfans/keeperfx/issues/$1)';
        $description = \preg_replace('/\#(\d{1,6})/', $replacement, $description);

        // Create the Embed
        $embed = new Embed();
        $embed->title($alpha_build->getName());
        $embed->color(self::COLOR_NEW_ALPHA_PATCH);
        $embed->timestamp($alpha_build->getTimestamp()->format('Y-m-d H:i'));
        $embed->url($_ENV['APP_ROOT_URL'] . '/download/alpha/' . $alpha_build->getFilename());
        $embed->description($description);
        $embed->footer(BinaryFormatter::bytes($alpha_build->getSizeInBytes())->format());

        // Send the embed
        return $this->sendEmbed($embed, "New alpha patch!");
    }

    public function notifyNewStableBuild(GithubRelease $github_release): bool
    {
        if($this->webhook === null){
            return false;
        }

        // Create the Embed
        $embed = new Embed();
        $embed->title($github_release->getName());
        $embed->color(self::COLOR_NEW_STABLE_BUILD);
        $embed->timestamp($github_release->getTimestamp()->format('Y-m-d H:i'));
        $embed->url($github_release->getDownloadUrl());
        // $embed->thumbnail($_ENV['APP_ROOT_URL'] . '/img/horny-face-512.png');
        $embed->thumbnail($_ENV['APP_ROOT_URL'] . '/img/download.png');
        $embed->footer(BinaryFormatter::bytes($github_release->getSizeInBytes())->format());
        $embed->description("A new game update!");

        // Send the embed
        return $this->sendEmbed($embed, "New game update!");
    }
}
