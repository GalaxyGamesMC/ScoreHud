<?php

declare(strict_types=1);

/**
 *     _____                    _   _           _
 *    /  ___|                  | | | |         | |
 *    \ `--.  ___ ___  _ __ ___| |_| |_   _  __| |
 *     `--. \/ __/ _ \| '__/ _ \  _  | | | |/ _` |
 *    /\__/ / (_| (_) | | |  __/ | | | |_| | (_| |
 *    \____/ \___\___/|_|  \___\_| |_/\__,_|\__,_|
 *
 * ScoreHud, a Scoreboard plugin for PocketMine-MP
 * Copyright (c) 2020 Ifera  < https://github.com/Ifera >
 *
 * Discord: Ifera#3717
 * Twitter: ifera_tr
 *
 * This software is distributed under "GNU General Public License v3.0".
 * This license allows you to use it and/or modify it but you are not at
 * all allowed to sell this plugin at any cost. If found doing so the
 * necessary action required would be taken.
 *
 * ScoreHud is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License v3.0 for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-3.0>.
 * ------------------------------------------------------------------------
 */

namespace Ifera\ScoreHud;

use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\event\PlayerTagUpdateEvent;
use Ifera\ScoreHud\event\ServerTagsUpdateEvent;
use Ifera\ScoreHud\event\ServerTagUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use Ifera\ScoreHud\session\PlayerManager;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use function is_null;

class EventListener implements Listener
{

    public function onWorldChange(EntityTeleportEvent $event): void
    {
        if (!ScoreHudSettings::isMultiWorld()) {
            return;
        }

        $from = $event->getFrom();
        $to = $event->getTo();

        if ($from->getWorld()->getFolderName() === $to->getWorld()->getFolderName()) {
            return;
        }

        $player = $event->getEntity();

        if (!$player instanceof Player or !$player->spawned) {
            return;
        }

        PlayerManager::getNonNull($player)->handle($to->getWorld()->getFolderName());
    }

    public function onServerTagUpdate(ServerTagUpdateEvent $event): void
    {
        $this->updateServerTag($event->getTag());
    }

    private function updateServerTag(ScoreTag $tag): void
    {
        foreach (PlayerManager::getAll() as $session) {
            $this->updateTag($session->getPlayer(), $tag);
        }
    }

    private function updateTag(Player $player, ScoreTag $newTag): void
    {
        if (
            !$player->isOnline() ||
            ScoreHudSettings::isInDisabledWorld($player->getWorld()->getFolderName()) ||
            is_null($session = PlayerManager::get($player)) ||
            is_null($scoreboard = $session->getScoreboard()) ||
            is_null($scoreTag = $scoreboard->getTag($newTag->getName()))
        ) {
            return;
        }

        $scoreTag->setValue($newTag->getValue());

        if (ScoreHudSettings::isSingleLineUpdateMode()) $scoreboard->handleSingleTagUpdate($scoreTag);
        else $scoreboard->update()->display();
    }

    public function onServerTagsUpdate(ServerTagsUpdateEvent $event): void
    {
        foreach ($event->getTags() as $tag) {
            $this->updateServerTag($tag);
        }
    }

    public function onPlayerTagUpdate(PlayerTagUpdateEvent $event): void
    {
        $this->updateTag($event->getPlayer(), $event->getTag());
    }

    public function onPlayerTagsUpdate(PlayerTagsUpdateEvent $event): void
    {
        foreach ($event->getTags() as $tag) {
            $this->updateTag($event->getPlayer(), $tag);
        }
    }
}