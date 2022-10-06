<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Error;

class GeoIP extends CBitrixComponent implements Controllerable
{
	public function configureActions(): array
	{
		return [];
	}

	/**
	 * Возвращает город
	 * Если запись с ip уже есть в hl блоке, возвращается название города из hl блока
	 * Если записи нет, отправляется запрос в dadata, добавляется запись в hl блока и возвращается город
	 *
	 * @param string $ip
	 * @return AjaxJson|mixed|string
	 */
	public function getCityAction(string $ip)
	{
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			$result = new Result();
			$result->addError(new Error('Некорректный IP'));
			return AjaxJson::createError($result->getErrorCollection());
		}

		$entityDataClass = $this->getEntityDataClass();

		$geoData = $entityDataClass::getList([
			'select' => ['*'],
			'filter' => ['=UF_IP' => $ip]
		])->fetch();

		// Если запись с ip уже есть в hl блоке, возвращается название города из hl блока
		if ($geoData) {
			return $geoData['UF_CITY'];
		} else {
			$response = $this->geoDataRequest($ip);
			try {
				if ($response->family == 'CLIENT_ERROR') {
					throw new ErrorException($response->message);
				}
			} catch (ErrorException $e) {
				$this->sendMail($e->getMessage());
			}

			// Если сервис смог определить местоположение, добавляется запись в hl блок
			if ($response->location) {
				$entityDataClass::add([
					'UF_IP' => $ip,
					'UF_CITY' => $response->location->data->region
				]);
				return $response->location->data->region;
			} else {
				return 'Город не удалось определить';
			}
		}
	}

	/**
	 * Отправляет сообщение на почту
	 *
	 * @param $message
	 */
	protected function sendMail(string $message)
	{
		Event::send([
			"EVENT_NAME" => "ERROR",
			"LID" => "s1",
			"C_FIELDS" => [
				"MESSAGE" => $message,
			],
		]);
	}

	/**
	 *  Выполняет запрос в сервис dadata для получения гео-данных по ip
	 *
	 * @param string $ip
	 * @return array|float|int|mixed|object|string|void|null
	 */
	protected function geoDataRequest(string $ip)
	{
		$httpClient = new HttpClient();
		$httpClient->setHeader('Content-Type', 'application/json', true);
		$httpClient->setHeader('Accept', 'application/json', true);
		$httpClient->setHeader('Authorization', 'Token e986c1359c3622104a1daf753ba8509c01da7c90', true);

		$response = $httpClient->post(
			'https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address',
			json_encode(['ip' => $ip])
		);

		return json_decode($response);
	}

	protected function getEntityDataClass()
	{
		Loader::includeModule("highloadblock");

		$hlBlock = HighloadBlockTable::getList([
			'select' => ['*'],
			'filter' => ['=NAME' => 'GeoIP']
		])->fetch();
		return HighloadBlockTable::compileEntity($hlBlock)->getDataClass();
	}

	public function executeComponent()
	{
		$this->includeComponentTemplate();
	}
}