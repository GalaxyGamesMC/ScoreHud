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

use Ifera\ScoreHud\commands\ScoreHudCommand;
use Ifera\ScoreHud\factory\TagsFactory;
use Ifera\ScoreHud\session\PlayerManager;
use Ifera\ScoreHud\session\PlayerSessionHandler;
use Ifera\ScoreHud\task\ScoreUpdateTitleTask;
use Ifera\ScoreHud\utils\HelperUtils;
use Ifera\ScoreHud\utils\TitleUtils;
use Ifera\ScoreHud\utils\Utils;
use jackmd\scorefactory\ScoreFactory;
use jackmd\scorefactory\ScoreFactoryException;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use function is_array;

class ScoreHud extends PluginBase
{
    use SingletonTrait;

    private const CONFIG_VERSION = 11;
    private const SCOREHUD_VERSION = 3;

    private ?Config $scoreConfig = null;

    public function onLoad(): void
    {
        self::setInstance($this);
    }

    public function onEnable(): void
    {
        $this->loadConfigs();

        if (!Utils::validateVirions($this)) {
            return;
        }

        ScoreHudSettings::init($this);

        if (!$this->canLoad()) {
            return;
        }

        if (ScoreHudSettings::isTimezoneChanged()) {
            if (Utils::setTimezone()) {
                $this->getLogger()->notice("Server timezone successfully set to " . ScoreHudSettings::getTimezone());
            } else {
                $this->getLogger()->error("Unable to set timezone. Invalid timezone: " . ScoreHudSettings::getTimezone() . ", provided under 'time.zone' in config.yml.");
            }
        }

        if (ScoreHudSettings::areFlickeringTitlesEnabled()) {
            $this->getScheduler()->scheduleRepeatingTask(new ScoreUpdateTitleTask($this), ScoreHudSettings::getFlickerRate());
        }

        $this->getServer()->getPluginManager()->registerEvents(new PlayerSessionHandler(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->getServer()->getCommandMap()->register("scorehud", new ScoreHudCommand($this));

        if (ScoreHudSettings::isTagFactoryEnabled()) TagsFactory::init($this);
    }

    private function loadConfigs(): void
    {
        $this->saveDefaultConfig();

        $this->saveResource("scorehud.yml");
        $this->scoreConfig = new Config($this->getDataFolder() . "scorehud.yml", Config::YAML);
    }

    private function canLoad(): bool
    {
        $load = true;
        $errors = [];

        if (!ScoreHudSettings::isMultiWorld() && empty(ScoreHudSettings::getDefaultBoard())) {
            $load = false;
            $errors[] = "Please set the lines under 'default-board' properly, in scorehud.yml.";
        }

        if (ScoreHudSettings::useDefaultBoard() && empty(ScoreHudSettings::getDefaultBoard())) {
            $load = false;
            $errors[] = "Please set the lines under 'default-board' properly, in scorehud.yml.";
        }

        if (ScoreHudSettings::areFlickeringTitlesEnabled() && empty(ScoreHudSettings::getTitles())) {
            $load = false;
            $errors[] = "Please set the lines under 'titles.lines' properly, in scorehud.yml.";
        }

        if (!is_array($this->getConfig()->get("disabled-worlds", []))) {
            $load = false;
            $errors[] = "The 'disabled-worlds' key in config.yml must be of the type array. Please set it properly.";
        }

        if (!$load) {
            foreach ($errors as $error) {
                $this->getLogger()->error($error);
            }

            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        return $load;
    }

    public function onDisable(): void
    {
        ScoreHudSettings::destroy();
        PlayerManager::destroyAll();
    }

    public function getScoreConfig(): Config
    {
        return $this->scoreConfig;
    }

    /**
     * @throws ScoreFactoryException
     */
    public function setScore(Player $player, bool $calledFromTask): void
    {
        if (!$player->isOnline()) {
            return;
        }

        if (HelperUtils::isDisabled($player) || ScoreHudSettings::isInDisabledWorld($player->getWorld()->getFolderName())) {
            ScoreFactory::removeObjective($player);

            return;
        }

        ScoreFactory::setObjective($player, TitleUtils::getTitle($calledFromTask));
        ScoreFactory::sendObjective($player);
    }
}
