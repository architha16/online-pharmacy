import os
from dotenv import load_dotenv
from pages.signin_page import SignInPage

load_dotenv()


def test_signin_page_loads(driver):
    signin = SignInPage(driver)

    signin.open("Customer")

    assert "Customer Login" in driver.title
    assert driver.find_element("name", "username").is_displayed()
    assert driver.find_element("id", "password").is_displayed()
    assert driver.find_element("name", "signin").is_displayed()


def test_invalid_username_and_password(driver):
    signin = SignInPage(driver)

    signin.open("Customer")
    signin.login("wrong_test_user", "wrong_test_password")

    assert "Invalid Username or Password." in signin.get_error_message()


def test_invalid_password(driver):
    signin = SignInPage(driver)

    signin.open("Customer")
    signin.login("architha", "wrong_password")

    assert "Incorrect Password." in signin.get_error_message()


def test_valid_customer_login(driver):
    signin = SignInPage(driver)

    signin.open("Customer")

    username = os.getenv("PHARMACYX_TEST_CUSTOMER_USERNAME")
    password = os.getenv("PHARMACYX_TEST_CUSTOMER_PASSWORD")

    assert username is not None, "PHARMACYX_TEST_CUSTOMER_USERNAME is not set."
    assert password is not None, "PHARMACYX_TEST_CUSTOMER_PASSWORD is not set."

    signin.login(username, password)

    if "products.php" not in driver.current_url:
        print("\nCurrent URL:", driver.current_url)
        print("Login error:", signin.get_error_message())

    assert "products.php" in driver.current_url