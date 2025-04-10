(function ($) {
  console.log('Script started');  // Проверка запуска скрипта

  // Обработка состояния чекбокса
  $("#privacy").on("change", function() {
    console.log('Checkbox changed');  // Проверка работы чекбокса
    $(".contact-form__button").prop("disabled", !this.checked);
  });

  $(".contact-form").submit(function (event) {
    console.log('Form submitted');  // Проверка отправки формы
    event.preventDefault();

    // Проверяем состояние чекбокса
    if (!$("#privacy").prop("checked")) {
      console.log('Privacy not checked');  // Проверка состояния чекбокса
      return false;
    }

    const form = this;
    
    // Сообщения формы
    const successSendText = "Сообщение успешно отправлено";
    const errorSendText = "Сообщение не отправлено. Попробуйте еще раз!";
    const requiredFieldsText = "Заполните поля с именем и телефоном";
    
    // Проверяем доступность геолокации
    if ("geolocation" in navigator) {
      console.log('Geolocation is available');  // Проверка доступности геолокации
      
      navigator.geolocation.getCurrentPosition(
        // Успешное получение геолокации
        position => {
          console.log('Geolocation success:', position);  // Проверка успешного получения геолокации
          const locationData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          };
          submitFormData(locationData);
        },
        // Ошибка получения геолокации
        error => {
          console.log('Geolocation error:', error.code, error.message);  // Детальный вывод ошибки
          submitFormData();
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,  // Увеличили timeout до 10 секунд
          maximumAge: 0
        }
      );
    } else {
      console.log('Geolocation is NOT available');  // Проверка недоступности геолокации
      submitFormData();
    }

    // Функция отправки формы
    function submitFormData(locationData = null) {
      console.log('Submitting form with location:', locationData);  // Проверка данных отправки
      
      const fd = new FormData(form);
      
      if (locationData) {
        fd.append("latitude", locationData.latitude);
        fd.append("longitude", locationData.longitude);
      }

      $.ajax({
        url: "/telegramform/php/send-message-to-telegram.php",
        type: "POST",
        data: fd,
        processData: false,
        contentType: false,
        beforeSend: () => {
          console.log('Ajax sending started');  // Проверка начала отправки
          $(".preloader").addClass("preloader_active");
        },
        success: function(res) {
          console.log('Ajax response:', res);  // Проверка ответа сервера
          $(".preloader").removeClass("preloader_active");
          const message = $(form).find(".contact-form__message");
          const respond = $.parseJSON(res);

          if (respond === "SUCCESS") {
            message.text(successSendText).css("color", "#21d4bb");
            form.reset();
            $(".contact-form__button").prop("disabled", true);
            setTimeout(() => {
              message.text("");
            }, 4000);
          } else if (respond === "NOTVALID") {
            message.text(requiredFieldsText).css("color", "#d42121");
            setTimeout(() => {
              message.text("");
            }, 3000);
          } else {
            message.text(errorSendText).css("color", "#d42121");
            setTimeout(() => {
              message.text("");
            }, 4000);
          }
        },
        error: function(xhr, status, error) {
          console.log('Ajax error:', status, error);  // Проверка ошибок Ajax
          $(".preloader").removeClass("preloader_active");
        }
      });
    }
  });
})(jQuery);