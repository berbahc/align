<?php

test('redirects guests from the homepage to the login page', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/login');
});
