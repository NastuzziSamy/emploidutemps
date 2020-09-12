#!/bin/env python3

from subprocess import Popen, PIPE

running_procs = [Popen(['php', 'moulinette.php', str(i * 100)]) for i in range(34)]