<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Modify database for caption/subtitle/chapter support.
 *
 * @package    AccesstoMemory
 * @subpackage migration
 */
class arMigration0191
{
    const VERSION = 191;
    const MIN_MILESTONE = 2;

    public function up($configuration)
    {
        // Add CSV Validator settings.
        if (null === QubitSetting::getByName('csv_validator_default_import_behaviour')) {
            $setting = new QubitSetting();
            $setting->name = 'csv_validator_default_import_behaviour';
            $setting->value = SettingsCsvValidatorAction::VALIDATOR_OFF;
            $setting->editable = 1;
            $setting->source_culture = 'en';
            $setting->save();
        }

        return true;
    }
}
