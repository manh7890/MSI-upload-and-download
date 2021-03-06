<?php
/**
* Copyright (c) Microsoft Corporation.  All Rights Reserved.  Licensed under the MIT License.  See License in the project root for license information.
* 
* RemoveContentFooterAction File
* PHP version 7
*
* @category  Library
* @package   Microsoft.Graph
* @copyright (c) Microsoft Corporation. All rights reserved.
* @license   https://opensource.org/licenses/MIT MIT License
* @link      https://graph.microsoft.com
*/
namespace Beta\Microsoft\Graph\Security\Model;
/**
* RemoveContentFooterAction class
*
* @category  Model
* @package   Microsoft.Graph
* @copyright (c) Microsoft Corporation. All rights reserved.
* @license   https://opensource.org/licenses/MIT MIT License
* @link      https://graph.microsoft.com
*/
class RemoveContentFooterAction extends InformationProtectionAction
{
    /**
    * Gets the uiElementNames
    *
    * @return string|null The uiElementNames
    */
    public function getUiElementNames()
    {
        if (array_key_exists("uiElementNames", $this->_propDict)) {
            return $this->_propDict["uiElementNames"];
        } else {
            return null;
        }
    }

    /**
    * Sets the uiElementNames
    *
    * @param string $val The value of the uiElementNames
    *
    * @return RemoveContentFooterAction
    */
    public function setUiElementNames($val)
    {
        $this->_propDict["uiElementNames"] = $val;
        return $this;
    }
}
