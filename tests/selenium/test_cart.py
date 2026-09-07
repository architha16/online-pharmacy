from test_products import login_as_customer
from pages.cart_page import CartPage


def test_cart_page_loads(driver):

    login_as_customer(driver)

    cart = CartPage(driver)
    cart.open()

    assert "My Cart | PharmacyX" in cart.get_title()


def test_cart_page_has_shopping_option(driver):

    login_as_customer(driver)

    cart = CartPage(driver)
    cart.open()

    assert cart.get_continue_shopping_button().is_displayed()


def test_cart_page_structure(driver):

    login_as_customer(driver)

    cart = CartPage(driver)
    cart.open()

    assert (
        len(cart.get_empty_cart_message()) > 0
        or len(cart.get_cart_items()) > 0
    )


def test_cart_quantity_buttons_if_items_exist(driver):

    login_as_customer(driver)

    cart = CartPage(driver)
    cart.open()

    items = cart.get_cart_items()

    if len(items) > 0:
        assert len(cart.get_quantity_buttons()) > 0


def test_cart_remove_buttons_if_items_exist(driver):

    login_as_customer(driver)

    cart = CartPage(driver)
    cart.open()

    items = cart.get_cart_items()

    if len(items) > 0:
        assert len(cart.get_remove_buttons()) > 0


def test_cart_checkout_state(driver):

    login_as_customer(driver)

    cart = CartPage(driver)
    cart.open()

    empty_cart = cart.get_empty_cart_message()

    buy_button = cart.get_buy_button()
    locked_button = cart.get_locked_buy_button()

    if len(empty_cart) > 0:
        assert empty_cart[0].is_displayed()

    else:
        assert len(buy_button) > 0 or len(locked_button) > 0