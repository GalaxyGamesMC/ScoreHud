<?php

declare(strict_types=1);

namespace Ifera\ScoreHud\factory\listener;

use Ifera\ScoreHud\event\TagsResolveEvent;
use Ifera\ScoreHud\ScoreHud;
use Ifera\ScoreHud\ScoreHudSettings;
use pocketmine\event\Listener;
use pocketmine\utils\Process;
use function count;
use function date;
use function explode;
use function number_format;
use function round;

class TagResolveListener implements Listener
{

    public function __construct(
        private readonly ScoreHud $plugin
    )
    {
    }

    public function onTagResolve(TagsResolveEvent $event)
    {
        $player = $event->getPlayer();
        $tag = $event->getTag();
        $tags = explode('.', $tag->getName(), 2);
        $value = "";

        if ($tags[0] !== 'scorehud' || count($tags) < 2) return;

        $value = match ($tags[1]) {
            "name", "real_name" => $player->getName(),
            "display_name" => $player->getDisplayName(),
            "online" => count($player->getServer()->getOnlinePlayers()),
            "max_online" => $player->getServer()->getMaxPlayers(),
            "item_name" => $player->getInventory()->getItemInHand()->getName(),
            "item_id" => $player->getInventory()->getItemInHand()->getId(),
            "item_meta" => $player->getInventory()->getItemInHand()->getMeta(),
            "item_count" => $player->getInventory()->getItemInHand()->getCount(),
            "x" => (int)$player->getPosition()->getX(),
            "y" => (int)$player->getPosition()->getY(),
            "z" => (int)$player->getPosition()->getZ(),
            "load" => $player->getServer()->getTickUsage(),
            "tps" => $player->getServer()->getTicksPerSecond(),
            "level_name", "world_name" => $player->getWorld()->getDisplayName(),
            "level_folder_name", "world_folder_name" => $player->getWorld()->getFolderName(),
            "ip" => $player->getNetworkSession()->getIp(),
            "ping" => $player->getNetworkSession()->getPing(),
            "health" => (int)$player->getHealth(),
            "max_health" => $player->getMaxHealth(),
            "xp_level" => (int)$player->getXpManager()->getXpLevel(),
            "xp_progress" => (int)$player->getXpManager()->getXpProgress(),
            "xp_remainder" => (int)$player->getXpManager()->getRemainderXp(),
            "xp_current_total" => (int)$player->getXpManager()->getCurrentTotalXp(),
            "time" => date(ScoreHudSettings::getTimeFormat()),
            "date" => date(ScoreHudSettings::getDateFormat()),
            "world_player_count" => count($player->getWorld()->getPlayers()),
            default => null
        };

        if (ScoreHudSettings::areMemoryTagsEnabled()) {
            $rUsage = Process::getRealMemoryUsage();
            $mUsage = Process::getAdvancedMemoryUsage();

            $globalMemory = "MAX";
            if ($this->plugin->getServer()->getConfigGroup()->getProperty("memory.global-limit") > 0) {
                $globalMemory = number_format(round($this->plugin->getServer()->getConfigGroup()->getProperty("memory.global-limit"), 2), 2) . " MB";
            }

            $value = match ($tags[1]) {
                "memory_main_thread" => number_format(round(($mUsage[0] / 1024) / 1024, 2), 2) . " MB",
                "memory_total" => number_format(round(($mUsage[1] / 1024) / 1024, 2), 2) . " MB",
                "memory_virtual" => number_format(round(($mUsage[2] / 1024) / 1024, 2), 2) . " MB",
                "memory_heap" => number_format(round(($rUsage[0] / 1024) / 1024, 2), 2) . " MB",
                "memory_global" => $globalMemory,
                default => null, // or handle the case when the tag is not recognized
            };
        }

        $tag->setValue((string)$value);
    }
}