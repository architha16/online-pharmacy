from selenium.webdriver.common.by import By


class SignInPage:

    USERNAME = (By.NAME, "username")
    PASSWORD = (By.ID, "password")
    LOGIN_BUTTON = (By.NAME, "signin")
    ERROR_MESSAGE = (By.CSS_SELECTOR, ".error")
    REGISTER_LINK = (By.LINK_TEXT, "Create Customer Account")
    BACK_HOME_LINK = (By.LINK_TEXT, "← Back to Home")

    def __init__(self, driver):
        self.driver = driver

    def open(self, role="Customer"):
        self.driver.get(
            f"http://localhost/onlinepharmacy/signin.php?role={role}"
        )

    def enter_username(self, username):
        self.driver.find_element(*self.USERNAME).clear()
        self.driver.find_element(*self.USERNAME).send_keys(username)

    def enter_password(self, password):
        self.driver.find_element(*self.PASSWORD).clear()
        self.driver.find_element(*self.PASSWORD).send_keys(password)

    def click_login(self):
        self.driver.find_element(*self.LOGIN_BUTTON).click()

    def login(self, username, password):
        self.enter_username(username)
        self.enter_password(password)
        self.click_login()

    def get_error_message(self):
        return self.driver.find_element(*self.ERROR_MESSAGE).text