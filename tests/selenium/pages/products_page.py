from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


class ProductsPage:

    def __init__(self, driver):
        self.driver = driver
        self.wait = WebDriverWait(driver, 10)

    def open(self):
        self.driver.get(
            "http://localhost/onlinepharmacy/products.php"
        )

    def get_title(self):
        return self.driver.title

    def search(self, medicine):
        search_box = self.wait.until(
            EC.visibility_of_element_located(
                (By.NAME, "search")
            )
        )

        search_box.clear()
        search_box.send_keys(medicine)

        search_button = self.wait.until(
            EC.element_to_be_clickable(
                (
                    By.CSS_SELECTOR,
                    "form button[type='submit']"
                )
            )
        )

        self.driver.execute_script(
            "arguments[0].scrollIntoView({block:'center'});",
            search_button
        )

        try:
            search_button.click()
        except Exception:
            self.driver.execute_script(
                "arguments[0].click();",
                search_button
            )

    def get_search_box(self):
        return self.wait.until(
            EC.visibility_of_element_located(
                (By.NAME, "search")
            )
        )

    def get_product_heading(self):
        return self.wait.until(
            EC.visibility_of_element_located(
                (By.CSS_SELECTOR, ".product-heading")
            )
        )

    def get_product_cards(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".products-container"
        )

    def get_add_to_cart_buttons(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            "button.cart-button"
        )

    def get_buy_now_buttons(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            "button.buy-button"
        )

    def click_buy_now(self, index=0):

        buttons = self.wait.until(
            EC.presence_of_all_elements_located(
                (
                    By.CSS_SELECTOR,
                    "button.buy-button"
                )
            )
        )

        button = buttons[index]

        self.driver.execute_script(
            "arguments[0].scrollIntoView({block:'center'});",
            button
        )

        try:
            self.wait.until(
                EC.element_to_be_clickable(
                    (
                        By.CSS_SELECTOR,
                        "button.buy-button"
                    )
                )
            )

            button.click()

        except Exception:
            self.driver.execute_script(
                "arguments[0].click();",
                button
            )

    def get_cart_button(self):
        return self.wait.until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".customer-cart-button"
                )
            )
        )