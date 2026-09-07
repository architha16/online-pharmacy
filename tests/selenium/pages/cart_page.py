from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


class CartPage:

    def __init__(self, driver):
        self.driver = driver

    def open(self):
        self.driver.get(
            "http://localhost/onlinepharmacy/cart.php"
        )

    def get_title(self):
        return self.driver.title

    def get_continue_shopping_button(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            ".continueBtn"
        )

    def get_cart_items(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            "tbody tr"
        )

    def get_quantity_buttons(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".qtyBtn"
        )

    def get_remove_buttons(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".removeBtn"
        )

    def get_buy_button(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".buyBtn"
        )

    def get_locked_buy_button(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".disabledBuyBtn"
        )

    def get_empty_cart_message(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".empty"
        )