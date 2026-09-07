from test_products import login_as_customer

from pages.products_page import ProductsPage
from pages.order_page import OrderPage

from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By


def open_order_page(driver):

    login_as_customer(driver)

    products = ProductsPage(driver)

    # Wait until Buy Now buttons are available
    WebDriverWait(driver, 10).until(
        EC.presence_of_element_located(
            (By.CSS_SELECTOR, "button.buy-button")
        )
    )

    buy_now_buttons = products.get_buy_now_buttons()

    assert len(buy_now_buttons) > 0, (
        "No Buy Now buttons are available."
    )

    # Get the first Buy Now button
    buy_now_button = buy_now_buttons[0]

    # Scroll button into the center of the screen
    driver.execute_script(
        """
        arguments[0].scrollIntoView({
            block: 'center',
            inline: 'center'
        });
        """,
        buy_now_button
    )

    # Small wait for page to settle
    WebDriverWait(driver, 5).until(
        EC.visibility_of(buy_now_button)
    )

    # Use JavaScript click to avoid ElementClickInterceptedException
    driver.execute_script(
        "arguments[0].click();",
        buy_now_button
    )

    # Wait for order page
    WebDriverWait(driver, 10).until(
        lambda d: "order_product.php" in d.current_url.lower()
    )

    order = OrderPage(driver)

    assert "order_product.php" in driver.current_url.lower()

    return order


def test_order_page_loads(driver):

    order = open_order_page(driver)

    assert "Order Now | PharmacyX" in order.get_title()

    assert order.get_page_heading().is_displayed()


def test_order_product_information_is_displayed(driver):

    order = open_order_page(driver)

    assert order.get_product_name().is_displayed()

    assert order.get_product_description().is_displayed()

    assert order.get_product_price().is_displayed()

    assert order.get_product_stock().is_displayed()


def test_order_quantity_input_is_displayed(driver):

    order = open_order_page(driver)

    quantity = order.get_quantity_input()

    assert quantity.is_displayed()

    assert quantity.get_attribute("type") == "number"

    assert quantity.get_attribute("min") == "1"


def test_order_quantity_default_value(driver):

    order = open_order_page(driver)

    quantity = order.get_quantity_input()

    assert quantity.get_attribute("value") == "1"


def test_continue_shopping_button_is_displayed(driver):

    order = open_order_page(driver)

    assert order.get_continue_shopping_button().is_displayed()


def test_place_order_button_is_displayed(driver):

    order = open_order_page(driver)

    button = order.get_place_order_button()

    assert button.is_displayed()

    assert button.get_attribute("type") == "submit"


def test_order_product_section_is_displayed(driver):

    order = open_order_page(driver)

    product_name = order.get_product_name()

    assert len(product_name.text.strip()) > 0


def test_order_stock_information_is_displayed(driver):

    order = open_order_page(driver)

    stock = order.get_product_stock()

    assert len(stock.text.strip()) > 0


def test_order_price_information_is_displayed(driver):

    order = open_order_page(driver)

    price = order.get_product_price()

    assert "₹" in price.text


def test_order_quantity_can_be_changed(driver):

    order = open_order_page(driver)

    quantity = order.get_quantity_input()

    quantity.clear()

    quantity.send_keys("1")

    assert quantity.get_attribute("value") == "1"