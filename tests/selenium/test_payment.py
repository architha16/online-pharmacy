from test_products import login_as_customer
from selenium.webdriver.common.by import By

from pages.products_page import ProductsPage
from pages.order_page import OrderPage
from pages.payment_page import PaymentPage


def open_payment_page(driver):

    # -------------------------------------------------
    # LOGIN
    # -------------------------------------------------

    login_as_customer(driver)

    # -------------------------------------------------
    # PRODUCTS PAGE
    # -------------------------------------------------

    products = ProductsPage(driver)

    buy_buttons = products.get_buy_now_buttons()

    assert len(buy_buttons) > 0, (
        "No Buy Now buttons are available."
    )

    # -------------------------------------------------
    # FIND A NON-PRESCRIPTION MEDICINE
    # -------------------------------------------------

    non_prescription_button = None

    for button in buy_buttons:

        product_card = button.find_element(
            By.XPATH,
            "./ancestor::div[contains(@class, 'products-container')][1]"
        )

        if "Prescription Not Required" in product_card.text:
            non_prescription_button = button
            break

    assert non_prescription_button is not None, (
        "No non-prescription medicine is available."
    )

    # -------------------------------------------------
    # CLICK BUY NOW
    # -------------------------------------------------

    driver.execute_script(
        "arguments[0].scrollIntoView({block: 'center'});",
        non_prescription_button
    )

    # JavaScript click to avoid
    # ElementClickInterceptedException
    driver.execute_script(
        "arguments[0].click();",
        non_prescription_button
    )

    # -------------------------------------------------
    # ORDER NOW PAGE
    # -------------------------------------------------

    order = OrderPage(driver)

    assert "Order Now" in driver.title, (
        f"Expected Order Now page, "
        f"but current title is: {driver.title}"
    )

    # Get Place Order button
    place_order_button = order.get_place_order_button()

    assert place_order_button.is_displayed(), (
        "Place Order button is not displayed."
    )

    # Scroll to Place Order button
    driver.execute_script(
        "arguments[0].scrollIntoView({block: 'center'});",
        place_order_button
    )

    # JavaScript click to avoid
    # ElementClickInterceptedException
    driver.execute_script(
        "arguments[0].click();",
        place_order_button
    )

    # -------------------------------------------------
    # PAYMENT PAGE
    # -------------------------------------------------

    payment = PaymentPage(driver)

    return payment


# =========================================================
# PAYMENT PAGE TESTS
# =========================================================

def test_payment_page_loads(driver):

    payment = open_payment_page(driver)

    assert "Payment" in payment.get_title(), (
        f"Wrong page title: {payment.get_title()}"
    )


def test_payment_checkout_mode_is_displayed(driver):

    payment = open_payment_page(driver)

    checkout_mode = payment.get_checkout_mode()

    assert len(checkout_mode) > 0, (
        "Checkout mode is not displayed."
    )

    assert checkout_mode[0].is_displayed()


def test_payment_medicines_are_displayed(driver):

    payment = open_payment_page(driver)

    medicines = payment.get_medicines()

    assert len(medicines) > 0, (
        "No medicines are displayed on payment page."
    )


def test_payment_delivery_fields_are_displayed(driver):

    payment = open_payment_page(driver)

    delivery_fields = payment.get_delivery_fields()

    assert len(delivery_fields) > 0, (
        "Delivery fields are not displayed."
    )


def test_payment_methods_are_displayed(driver):

    payment = open_payment_page(driver)

    payment_methods = payment.get_payment_methods()

    assert len(payment_methods) > 0, (
        "Payment methods are not displayed."
    )


def test_payment_online_payment_section_is_displayed(driver):

    payment = open_payment_page(driver)

    # Select Online Payment first
    online_option = payment.get_online_option()

    assert online_option.is_displayed(), (
        "Online payment option is not displayed."
    )

    # JavaScript click avoids click interception
    driver.execute_script(
        "arguments[0].click();",
        online_option
    )

    # Now the online payment section should be visible
    online_section = payment.get_online_payment_section()

    assert online_section.is_displayed(), (
        "Online payment section is not displayed "
        "after selecting Online payment."
    )


def test_payment_online_payment_fields_are_displayed(driver):

    payment = open_payment_page(driver)

    # Select Online Payment first
    online_option = payment.get_online_option()

    driver.execute_script(
        "arguments[0].click();",
        online_option
    )

    online_fields = payment.get_online_payment_fields()

    assert len(online_fields) > 0, (
        "Online payment fields are not displayed."
    )


def test_payment_confirm_order_button_is_available(driver):

    payment = open_payment_page(driver)

    confirm_button = payment.get_confirm_order_button()

    assert len(confirm_button) > 0, (
        "Confirm Order button is not available."
    )

    assert confirm_button[0].is_displayed()


def test_back_button_is_displayed(driver):

    payment = open_payment_page(driver)

    back_button = payment.get_back_button()

    assert len(back_button) > 0, (
        "Back button is not displayed."
    )

    assert back_button[0].is_displayed()