{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author     PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{if $errors|@count > 0}
	<div class="error">
		<ul>
			{foreach from=$errors item=error}
				<li>{$error}</li>
			{/foreach}
		</ul>
	</div>
{/if}

<form action="{$request_uri}" method="post" class="defaultForm form-horizontal">
	<fieldset class="panel">
		<div class="panel-heading">
			<i class="icon-cogs"></i>{l s='Settings' mod='categorybackgroundmanager'}
		</div>	
		
		<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">{l s='Default background color' mod='categorybackgroundmanager'}</label>
				<div class="col-lg-3">
					<input type="text" size="20" name="CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR" value="{$CATEGORYBACKGROUNDMANAGER_DEFAULTCOLOR}" />
					<p class="help-block">{l s='e.g. "#fff" for the color white' mod='categorybackgroundmanager'}</p>
				</div>
			</div>



			<div class="form-group">
			
				<label class="control-label col-lg-3">
				 	{l s='Apply Background to sub-categories' mod='categorybackgroundmanager'}
				</label>

				<div class="col-lg-9">				
					<span class="switch prestashop-switch fixed-width-lg">
						<input type="radio" name="CATEGORYBACKGROUNDMANAGER_RECURSIVEBG" id="CATEGORYBACKGROUNDMANAGER_RECURSIVEBG_on" value="1" {if ($CATEGORYBACKGROUNDMANAGER_RECURSIVEBG)}checked="checked"{/if}>
						<label for="CATEGORYBACKGROUNDMANAGER_RECURSIVEBG_on">{l s='Yes' mod='categorybackgroundmanager'}</label>
						<input type="radio" name="CATEGORYBACKGROUNDMANAGER_RECURSIVEBG" id="CATEGORYBACKGROUNDMANAGER_RECURSIVEBG_off" value="0" {if (!$CATEGORYBACKGROUNDMANAGER_RECURSIVEBG)}checked="checked"{/if}>
						<label for="CATEGORYBACKGROUNDMANAGER_RECURSIVEBG_off">{l s='No' mod='categorybackgroundmanager'}</label>
					<a class="slide-button btn"></a>
					</span>
					
					
					<p class="help-block">
						{l s='Sub-categories get parent category background image and color' mod='categorybackgroundmanager'}
					</p>
				
				</div>
			
			</div>			
		</div>
	
	<div class="panel-footer">
		<button type="submit" name="{$submitName}" value="{l s='Save' mod='categorybackgroundmanager'}" class="btn btn-default pull-right">
			<i class="process-icon-save"></i>
			{l s='Save' mod='categorybackgroundmanager'}
		</button>
	</div>

	</fieldset>
</form>