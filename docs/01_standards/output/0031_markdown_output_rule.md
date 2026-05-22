# P2 - Markdown Output Rule (Revised)

## Purpose
Lock down the format used when the AI writes markdown files so it stays consistent with user preferences and avoids unnecessary characters.

## Mandatory Rule for .md
If the AI writes a markdown file:
- Output must be the FULL contents of the file path.
- Output must use only ONE code block as the main container.
- The outer fence must use triple backticks with the `text` language tag.
- Triple backticks (```) may appear only on the first and last lines of the entire message as the copy-paste wrapper.
- There must be no text, explanation, or greeting outside that code block.
- If code blocks are needed inside the markdown, use alternatives such as four-space indentation or blockquotes to avoid triple backticks or tildes (`~~~`) that could break the outer container.

## Scope of Rule
- This rule applies only when writing or presenting a `.md` file for the user to copy.
- This rule does not apply to ordinary discussion.

## Forbidden Behavior
- Do not include decorative ASCII characters or non-standard symbols.
- Do not include Bash code blocks inside the content. If command instructions are necessary, use plain text without code decoration.
- Do not write explanations outside the main code block when sending a markdown file.
- Do not use triple backticks inside markdown content; use an alternative format so the outer container does not break.
