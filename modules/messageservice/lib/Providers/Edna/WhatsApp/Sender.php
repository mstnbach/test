<?php

namespace Bitrix\MessageService\Providers\Edna\WhatsApp;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\MessageService\Providers;
use Bitrix\MessageService\Sender\Result\MessageStatus;
use Bitrix\MessageService\Sender\Result\SendMessage;

class Sender extends Providers\Base\Sender
{
	protected Providers\OptionManager $optionManager;
	protected Providers\SupportChecker $supportChecker;
	protected EdnaRu $utils;
	protected EmojiConverter $emoji;

	public function __construct(
		Providers\OptionManager $optionManager,
		Providers\SupportChecker $supportChecker,
		EdnaRu $utils,
		EmojiConverter $emoji
	)
	{
		$this->optionManager = $optionManager;
		$this->supportChecker = $supportChecker;
		$this->utils = $utils;
		$this->emoji = $emoji;
	}

	public function sendMessage(array $messageFields): SendMessage
	{
		if (!$this->supportChecker->canUse())
		{
			$result = new SendMessage();
			$result->addError(new Error('Service is unavailable'));

			return $result;
		}

		$paramsResult = $this->getSendMessageParams($messageFields);
		if (!$paramsResult->isSuccess())
		{
			$result = new SendMessage();
			$result->addErrors($paramsResult->getErrors());

			return $result;
		}

		$requestParams = $paramsResult->getData();
		$method = $this->getSendMessageMethod($messageFields);

		if ($this->isTemplateMessage($messageFields))
		{
			$this->sendHSMtoChat($messageFields);
		}

		$result = new SendMessage();

		$externalSender =
			new ExternalSender(
				$this->optionManager->getOption(Constants::API_KEY_OPTION),
				Constants::API_ENDPOINT
			)
		;

		$requestResult = $externalSender->callExternalMethod($method, $requestParams);
		if (!$requestResult->isSuccess())
		{
			$result->addErrors($requestResult->getErrors());

			return $result;
		}

		$apiData = $requestResult->getData();
		$result->setExternalId($apiData['requestId']);
		$result->setAccepted();

		return $result;
	}

	public function getMessageStatus(array $messageFields): MessageStatus
	{
		return new MessageStatus();
	}

	protected function getSendMessageMethod(array $messageFields): string
	{
		return 'cascade/schedule';
	}

	/**
	 * Converts message body text. Encodes emoji in the text, if there are any emoji.
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function prepareMessageBodyForSave(string $text): string
	{
		return $this->emoji->convertEmoji($text, Constants::EMOJI_ENCODE);
	}

	private function getSendMessageParams(array $messageFields): Result
	{
		$messageFields['MESSAGE_BODY'] = $this->emoji->convertEmoji($messageFields['MESSAGE_BODY'], Constants::EMOJI_DECODE);

		$cascadeResult = $this->getCascadeIdFromSubject($messageFields['MESSAGE_FROM']);
		if (!$cascadeResult->isSuccess())
		{
			return $cascadeResult;
		}

		$params = [
			'requestId' => uniqid('', true),
			'cascadeId' => $cascadeResult->getData()['cascadeId'],
			'subscriberFilter' => [
				'address' => str_replace('+', '', $messageFields['MESSAGE_TO']),
				'type' => 'PHONE',
			],
		];

		$params['content'] = $this->getMessageContent($messageFields);
		$result = new Result();
		$result->setData($params);

		return $result;
	}

	/**
	 * Checks if message is HSM template by message fields.
	 * We consider that it is template by mandatory text field.
	 * https://edna.docs.apiary.io/#reference/api/imouthsm
	 *
	 * @param array $messageFields Message fields.
	 *
	 * @return bool
	 */
	public function isTemplateMessage(array $messageFields): bool
	{
		if (isset($messageFields['MESSAGE_HEADERS']['template']['text']))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param array $messageFields
	 * @return array{whatsappContent: array}
	 */
	protected function getMessageContent(array $messageFields): array
	{
		$whatsAppContent =
			$this->isTemplateMessage($messageFields)
				? $this->getHSMContent($messageFields)
				: $this->getSimpleMessageContent($messageFields)
		;

		return [
			'whatsappContent' => $whatsAppContent
		];
	}

	/**
	 * @param array $messageFields
	 * @return array{contentType:string, text:string}
	 */
	private function getHSMContent(array $messageFields): array
	{
		$params['contentType'] = Constants::CONTENT_TYPE_TEXT;
		$params['text'] = $messageFields['MESSAGE_HEADERS']['template']['text'];

		foreach (['header', 'footer', 'keyboard'] as $templateField)
		{
			if (
				isset($messageFields['MESSAGE_HEADERS']['template'][$templateField])
				&& count($messageFields['MESSAGE_HEADERS']['template'][$templateField]) > 0
			)
			{
				$params[$templateField] = $messageFields['MESSAGE_HEADERS']['template'][$templateField];
			}
		}

		$params = $this->emoji->convertEmojiInTemplate($params, Constants::EMOJI_DECODE);

		return $params;
	}

	/**
	 * @param array $messageFields
	 * @return array{contentType:string, text:string}
	 */
	private function getSimpleMessageContent(array $messageFields): array
	{
		return [
			'contentType' => 'TEXT',
			'text' => $messageFields['MESSAGE_BODY']
		];
	}

	protected function getCascadeIdFromSubject(string $subject): Result
	{
		$result = new Result();

		$externalSender =
			new ExternalSender(
				$this->optionManager->getOption(Constants::API_KEY_OPTION),
				Constants::API_ENDPOINT
			)
		;

		/** @see https://docs.edna.ru/kb/poluchenie-informacii-o-kaskadah/ */
		$apiResult = $externalSender->callExternalMethod('cascade/get-all/', [
			'offset' => 0,
			'limit' => 0
		]);

		if (!$apiResult->isSuccess())
		{
			$result->addErrors($apiResult->getErrors());

			return $result;
		}

		$apiData = $apiResult->getData();
		foreach ($apiData as $cascade)
		{
			if ($cascade['status'] !== 'ACTIVE' || $cascade['stagesCount'] > 1)
			{
				continue;
			}

			if ($cascade['stages'][0]['subject']['subject'] === $subject)
			{
				$result->setData(['cascadeId' => $cascade['id']]);

				return $result;
			}
		}

		$result->addError(new Error('not cascade'));

		return $result;
	}


}