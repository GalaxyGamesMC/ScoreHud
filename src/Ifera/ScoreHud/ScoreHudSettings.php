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

use pocketmine\utils\Config;
use function in_array;

class ScoreHudSettings
{

    public const PREFIX = "§8[§l§6S§eH§r§8]§r ";

    private static ?Config $config;
    private static ?Config $scoreHud;

    private function __construct()
    {
    }

    public static function init(ScoreHud $plugin): void
    {
        self::$config = $plugin->getConfig();
        self::$scoreHud = $plugin->getScoreConfig();
    }

    public static function destroy(): void
    {
        self::$config = null;
        self::$scoreHud = null;
    }

    /*
     * Settings from config.yml
     */

    public static function isSingleLineUpdateMode(): bool
    {
        return self::getLineUpdateMode() === "single";
    }

    public static function getLineUpdateMode(): string
    {
        return strtolower(self::$config->getNested("line-update-mode", "single"));
    }

    public static function isTagFactoryEnabled(): bool
    {
        return boolval(self::$config->getNested("tag-factory.enable", true));
    }

    public static function getTagFactoryUpdatePeriod(): int
    {
        return intval(self::$config->getNested("tag-factory.update-period", 5));
    }

    public static function areMemoryTagsEnabled(): bool
    {
        return boolval(self::$config->getNested("tag-factory.enable-memory-tags", false));
    }

    /**
     * If multi world support is enabled and scoreboard for a world is not found then
     * check whether the user allows for using the default scoreboard instead.
     */
    public static function useDefaultBoard(): bool
    {
        return self::isMultiWorld() && (bool)self::$config->getNested("multi-world.use-default", false);
    }

    public static function isMultiWorld(): bool
    {
        return boolval(self::$config->getNested("multi-world.active", false));
    }

    public static function isInDisabledWorld(string $world): bool
    {
        return in_array($world, self::getDisabledWorlds());
    }

    public static function getDisabledWorlds(): array
    {
        return (array)self::$config->get("disabled-worlds", []);
    }

    public static function isTimezoneChanged(): bool
    {
        return self::$config->getNested("time.zone") !== false;
    }

    public static function getTimezone(): string
    {
        return (string)self::$config->getNested("time.zone", "America/New_York");
    }

    public static function getTimeFormat(): string
    {
        return (string)self::$config->getNested("time.format.time", "H:i:s");
    }

    public static function getDateFormat(): string
    {
        return (string)self::$config->getNested("time.format.date", "d-m-Y");
    }

    /*
     * Settings from scorehud.yml
     */

    public static function areFlickeringTitlesEnabled(): bool
    {
        return (bool)self::$scoreHud->getNested("titles.flicker", false);
    }

    public static function getFlickerRate(): int
    {
        return intval(self::$scoreHud->getNested("titles.period", 5)) * 20;
    }

    public static function getTitles(): array
    {
        return (array)self::$scoreHud->getNested("titles.lines", []);
    }

    public static function getTitle(): string
    {
        return strval(self::$scoreHud->getNested("titles.title", "§l§aServer §dName"));
    }

    public static function getDefaultBoard(): array
    {
        return (array)self::$scoreHud->get("default-board", []);
    }

    /**
     * Will return an array indexed by world name with their score lines.
     */
    public static function getScoreboards(): array
    {
        return (array)self::$scoreHud->get("scoreboards", []);
    }

    public static function worldExists(string $world): bool
    {
        return !empty(self::getScoreboard($world));
    }

    public static function getScoreboard(string $world): array
    {
        return (array)self::$scoreHud->getNested("scoreboards." . $world . ".lines", []);
    }
}