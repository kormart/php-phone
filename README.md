This is a PHP server for a very simple phone app working towards

<pre><code>-(void)appDidBecomeActive:(NSNotification*)notification {
    /*When the application becomes active after entering the background we try to connect to the robot*/
    [self setupRobotConnection];
}
</code></pre>

