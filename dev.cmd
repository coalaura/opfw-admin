@echo off

if EXIST ..\admin-panel-socket-js (
	echo %base%

	pushd ..\admin-panel-socket-js

	start bun main.js

	popd
)

start bun run dev

localhost -ro -c "..\.https\localhost.pem" -k "..\.https\localhost-key.pem"