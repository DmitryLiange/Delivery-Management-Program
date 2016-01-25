<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Программа управления доставкой</title>
    <script type="text/javascript" charset="utf-8">
        function check_date() {

            var date = document.firstForm.userDate.value;

            if (date != '') {

                document.firstForm.submit();
            } else {

                document.firstForm.check_button.disabled = false;
                alert("Пожалуйста, введите дату!");
            }
        }

        function change_login() {

            var login = document.secondForm.userLogin.value;
            var password = document.secondForm.userPassword.value;

            if (login != '' && password != '') {

                alert("Логин и пароль для подключения к API \"Мойсклад\" сменены!");
            } else {

                alert("Пожалуйста, заполните оба поля!");
            }
        }
    </script>
</head>
<body>
<p align="center">Пожалуйста, выберите день, для которого нужно распределить заказы, и статус обновления данных в "МойСклад":</p>
<form name="firstForm" method="post" action="main_script.php">
    <p align="center">Вносить изменения в "МойСклад?"
        <input type="checkbox" name="updateMoysklad" />
    </p>
    <p align="center">
        <input type="date" name="userDate"/>

        <input type="button" name="check_button" value="Запустить скрипт" onclick="disabled = true; check_date();"/>
    </p>
</form>
<br>
<p align="center">Заполните нижнюю форму для смены сотрудника аккаунта "МойСклад", от лица которого будут вноситься изменения в систему: </p>
<form name="secondForm" method="" action="">
    <p align="center">Новый логин:
        <input type="text" name = "userLogin" />
    </p>
    <p align="center">Новый пароль:
        <input type="password" name = "userPassword" />
    </p>
    <p align="center">
        <input type="button" name="do_button" value="Сменить логин и пароль" onclick="change_login();"/>
    </p>
</form>
</body>
</html>