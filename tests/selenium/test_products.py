import os

from dotenv import load_dotenv

from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

from pages.signin_page import SignInPage
from pages.products_page import ProductsPage


load_dotenv(".env")


# =========================================================
# CUSTOMER LOGIN
# =========================================================

def login_as_customer(driver):

    signin = SignInPage(driver)

    signin.open("Customer")

    username = os.getenv(
        "PHARMACYX_TEST_CUSTOMER_USERNAME"
    )

    password = os.getenv(
        "PHARMACYX_TEST_CUSTOMER_PASSWORD"
    )

    assert username is not None, (
        "PHARMACYX_TEST_CUSTOMER_USERNAME is not set."
    )

    assert password is not None, (
        "PHARMACYX_TEST_CUSTOMER_PASSWORD is not set."
    )

    # Perform login
    signin.login(
        username,
        password
    )

    # Wait until login redirects to Products page
    WebDriverWait(
        driver,
        10
    ).until(
        EC.url_contains("products.php")
    )

    # Final verification
    assert "products.php" in driver.current_url, (
        f"Customer login failed. "
        f"Current URL: {driver.current_url}"
    )


# =========================================================
# PRODUCTS PAGE TESTS
# =========================================================

def test_products_page_loads(driver):

    login_as_customer(driver)

    products = ProductsPage(driver)

    assert "Products | PharmacyX" in products.get_title()

    assert products.get_search_box().is_displayed()

    assert products.get_product_heading().is_displayed()

    assert products.get_cart_button().is_displayed()


def test_products_are_displayed(driver):

    login_as_customer(driver)

    products = ProductsPage(driver)

    product_cards = products.get_product_cards()

    assert len(product_cards) > 0, (
        "No products are displayed on the Products page."
    )


def test_add_to_cart_buttons_are_displayed(driver):

    login_as_customer(driver)

    products = ProductsPage(driver)

    add_to_cart_buttons = (
        products.get_add_to_cart_buttons()
    )

    assert len(add_to_cart_buttons) > 0, (
        "No Add To Cart buttons are displayed."
    )


def test_buy_now_buttons_are_displayed(driver):

    login_as_customer(driver)

    products = ProductsPage(driver)

    buy_now_buttons = (
        products.get_buy_now_buttons()
    )

    assert len(buy_now_buttons) > 0, (
        "No Buy Now buttons are displayed."
    )


def test_product_search(driver):

    login_as_customer(driver)

    products = ProductsPage(driver)

    products.search("medicine")

    # Wait for search URL
    WebDriverWait(
        driver,
        10
    ).until(
        EC.url_contains("search=medicine")
    )

    assert "search=medicine" in (
        driver.current_url.lower()
    )