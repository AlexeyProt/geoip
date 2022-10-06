<? use Bitrix\Main\UI\Extension;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
Extension::load('ui.bootstrap4');
?>
	<div class="main container">
		<? $APPLICATION->IncludeComponent('custom:geoip', '') ?>
	</div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>