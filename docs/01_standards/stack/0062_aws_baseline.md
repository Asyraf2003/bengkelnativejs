# P1 - AWS Baseline

## Purpose
Lock the chosen AWS infrastructure baseline.

## Rules
- Active baseline: CloudFront, S3, Lambda, SQS, DynamoDB.
- Non-AWS providers are considered inactive unless there is an explicit decision.
- Do not silently shift the provider baseline while working on another slice.
