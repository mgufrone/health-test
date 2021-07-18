pipeline {
  agent {
    kubernetes {
      inheritFrom "composer sonar"
      yaml '''
spec:
    containers:
    - name: python
      image: python:3.9-alpine
      command:
      - sleep
      args:
      - infinity
'''
    }
  }
  environment {
    GITHUB = credentials('github')
    SONAR_HOST_URL = credentials('sonar-url')
    SONAR_LOGIN = credentials('sonar-token')
  }
  stages {
    stage("Build") {
      steps {
        container("composer") {
          sh "composer install"
          sh "./vendor/bin/phpunit"
        }
      }
      post {
        always {
          container('sonar') {
            sh('sonar-scanner -Dsonar.login=$SONAR_LOGIN')
          }
        }
      }
    }
    stage("Build Tag") {
      when {
        not {
          buildingTag()
        }
        changelog '.*^bump version$'
      }
      steps {
        sh "git config user.email \"jenkins-bot@mgufron.com\""
        sh "git config user.name \"jenkins.bot\""
        sh "git fetch --tags"
        script {
          def releaseType = "patch"
          def lastTag = sh(script: "git describe --abbrev=0 --tags", returnStdout: true)
          lastTag = lastTag.replace("\n", "")
          def (major, minor, patch) = lastTag.tokenize(".")
          container("python") {
            sh "pip install gitchangelog"
            sh "apk add git"
            def notes = sh(script: "gitchangelog \"${lastTag}..\"", returnStdout: true)
            if (notes =~ /(?im)change(s?)\n\~/) {
              releaseType = "minor"
            }
            if (notes =~ /(?im)new\n\~/) {
              releaseType = "major"
            }
          }
          echo "$major, $minor, $patch"
          switch (releaseType) {
            case "major":
              major += 1
              break
            case "minor":
              minor += 1
              break
            case "patch":
              patch += 1
              break
          }
          env.currentVersion = [major, minor, patch].join(".")
          sh "git tag $currentVersion"
          sh "git push origin $currentVersion"
        }
      }
      post {
        success {
          build job: "php-bumper", parameters: [string(name: "VERSION", value: env.currentVersion), string(name: "PACKAGE", value: "mgufrone/healthcheck-bundle"), string(name: "BRANCH", value: "main")]
        }
      }
    }
  }
}
