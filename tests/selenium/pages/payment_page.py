from selenium.webdriver.common.by import By


class PaymentPage:

    def __init__(self, driver):
        self.driver = driver

    def open_cart_checkout(self):
        self.driver.get(
            "http://localhost/onlinepharmacy/paymentpage.php"
        )

    # -------------------------------------------------
    # PAGE TITLE
    # -------------------------------------------------

    def get_page_title(self):
        return self.driver.title

    # Compatibility with test_payment.py
    def get_title(self):
        return self.driver.title

    # -------------------------------------------------
    # CHECKOUT MODE
    # -------------------------------------------------

    def get_checkout_mode(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".checkout-mode"
        )

    # -------------------------------------------------
    # MEDICINES
    # -------------------------------------------------

    def get_medicines(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".medicine"
        )

    # -------------------------------------------------
    # DELIVERY INFORMATION
    # -------------------------------------------------

    def get_first_name_input(self):
        return self.driver.find_element(
            By.NAME,
            "first_name"
        )

    def get_last_name_input(self):
        return self.driver.find_element(
            By.NAME,
            "last_name"
        )

    def get_street_input(self):
        return self.driver.find_element(
            By.NAME,
            "street"
        )

    def get_city_input(self):
        return self.driver.find_element(
            By.NAME,
            "city"
        )

    def get_postal_code_input(self):
        return self.driver.find_element(
            By.NAME,
            "postal_code"
        )

    # Compatibility method
    def get_delivery_fields(self):

        fields = []

        for locator in [
            (By.NAME, "first_name"),
            (By.NAME, "last_name"),
            (By.NAME, "street"),
            (By.NAME, "city"),
            (By.NAME, "postal_code")
        ]:

            elements = self.driver.find_elements(*locator)

            fields.extend(elements)

        return fields

    # -------------------------------------------------
    # PAYMENT METHODS
    # -------------------------------------------------

    def get_cod_option(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            "input[name='payment_method'][value='COD']"
        )

    def get_online_option(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            "input[name='payment_method'][value='Online']"
        )

    # Compatibility method
    def get_payment_methods(self):

        return self.driver.find_elements(
            By.CSS_SELECTOR,
            "input[name='payment_method']"
        )

    # -------------------------------------------------
    # ONLINE PAYMENT
    # -------------------------------------------------

    def get_online_payment_section(self):
        return self.driver.find_element(
            By.ID,
            "onlinePayment"
        )

    def get_bank_input(self):
        return self.driver.find_element(
            By.NAME,
            "bank"
        )

    def get_payment_screenshot_input(self):
        return self.driver.find_element(
            By.NAME,
            "slip"
        )

    def get_remark_input(self):
        return self.driver.find_element(
            By.NAME,
            "remark"
        )

    # Compatibility method
    def get_online_payment_fields(self):

        fields = []

        for locator in [
            (By.NAME, "bank"),
            (By.NAME, "slip"),
            (By.NAME, "remark")
        ]:

            elements = self.driver.find_elements(*locator)

            fields.extend(elements)

        return fields

    # -------------------------------------------------
    # CONFIRM ORDER
    # -------------------------------------------------

    def get_confirm_order_button(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            "button[type='submit'][name='paynow']"
        )

    # -------------------------------------------------
    # WAITING BUTTON
    # -------------------------------------------------

    def get_waiting_button(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".waiting-btn"
        )

    # -------------------------------------------------
    # BACK BUTTON
    # -------------------------------------------------

    def get_back_button(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".back-btn"
        )