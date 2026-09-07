from selenium.webdriver.common.by import By


class OrderPage:

    def __init__(self, driver):
        self.driver = driver

    def get_title(self):
        return self.driver.title

    def get_page_heading(self):
        return self.driver.find_element(
            By.XPATH,
            "//h2[contains(normalize-space(), 'Place Your Order')]"
        )

    def get_product_name(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            ".order-product-name"
        )

    def get_product_description(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            ".order-product-description"
        )

    def get_product_price(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            ".order-product-price"
        )

    def get_product_stock(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            ".order-product-stock"
        )

    def get_quantity_input(self):
        return self.driver.find_element(
            By.ID,
            "quantity"
        )

    def get_continue_shopping_button(self):
        return self.driver.find_element(
            By.CSS_SELECTOR,
            ".continue-button"
        )

    def get_place_order_button(self):
        return self.driver.find_element(
            By.ID,
            "place-order-btn"
        )

    def get_prescription_message(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".prescription-message"
        )

    def get_otc_message(self):
        return self.driver.find_elements(
            By.CSS_SELECTOR,
            ".otc-message"
        )