def test_home_page(driver):
    driver.get("http://localhost/onlinepharmacy/")

    assert driver.current_url.startswith(
        "http://localhost/onlinepharmacy"
    )

    assert driver.find_element("tag name", "body").is_displayed()