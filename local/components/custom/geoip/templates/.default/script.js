(() => {
	let form = document.querySelector('#geoip');
	let errorMessage = form.querySelector('#errorMessage');
	let input = form.querySelector('#ip');
	let city = document.querySelector('#city');

	/**
	 * Отправляет IP и выводит связанный с ним город
	 */
	function submit() {
		event.preventDefault();

		let request = geoDataRequest();

		request.done((response) => {
			if (response.status === 'success') {
				city.innerHTML = response.data;
			} else {
				showError(response.errors[0].message);
			}
		});
	}

	/**
	 * Выполняет запрос для получения гео-данных
	 *
	 * @returns {*|jQuery}
	 */
	function geoDataRequest() {
		let query = {
			c: 'custom:geoip',
			action: 'getCity',
			mode: 'class'
		};

		let data = {
			ip: form.querySelector('#ip').value,
			SITE_ID: 's1',
			sessid: BX.message('bitrix_sessid')
		};

		return $.ajax({
			url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
			method: 'POST',
			data: data,
		});
	}

	/**
	 * Выводит сообщение об ошибке
	 *
	 * @param message
	 */
	function showError(message) {
		errorMessage.innerHTML = message;
		input.classList.add('is-invalid');
		city.innerHTML = '';
	}

	form.addEventListener('submit', submit);
})();