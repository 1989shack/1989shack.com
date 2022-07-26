GetCallerIdentity
PDF
Returns details about the IAM user or role whose credentials are used to call the operation.

Note
No permissions are required to perform this operation. If an administrator adds a policy to your IAM user or role that explicitly denies access to the sts:GetCallerIdentity action, you can still perform this operation. Permissions are not required because the same information is returned when an IAM user or role is denied access. To view an example response, see I Am Not Authorized to Perform: iam:DeleteVirtualMFADevice in the IAM User Guide.

Response Elements
The following elements are returned by the service.

Account
The AWS account ID number of the account that owns or contains the calling entity.

Type: String

Arn
The AWS ARN associated with the calling entity.

Type: String

Length Constraints: Minimum length of 20. Maximum length of 2048.

Pattern: [\u0009\u000A\u000D\u0020-\u007E\u0085\u00A0-\uD7FF\uE000-\uFFFD\u10000-\u10FFFF]+

UserId
The unique identifier of the calling entity. The exact value depends on the type of entity that is making the call. The values returned are those listed in the aws:userid column in the Principal table found on the Policy Variables reference page in the IAM User Guide.

Type: String

Errors
For information about the errors that are common to all actions, see Common Errors.

Examples
Example 1 - Called by an IAM user.
This example shows a request and response made with the credentials for a user named Alice in the AWS account 123456789012.

Sample Request
POST / HTTP/1.1
Host: sts.amazonaws.com
Accept-Encoding: identity
Content-Length: 32
Content-Type: application/x-www-form-urlencoded
Authorization: AWS4-HMAC-SHA256 Credential=AKIAI44QH8DHBEXAMPLE/20160126/us-east-1/sts/aws4_request,
        SignedHeaders=host;user-agent;x-amz-date, 
        Signature=1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef
X-Amz-Date: 20160126T215751Z
User-Agent: aws-cli/1.10.0 Python/2.7.3 Linux/3.13.0-76-generic botocore/1.3.22