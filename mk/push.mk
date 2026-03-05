push:
	@COUNT=$$(($$(git rev-list --count HEAD) + 1)); \
	git add .; \
	git commit -m "commit $$COUNT"; \
	git push origin main
